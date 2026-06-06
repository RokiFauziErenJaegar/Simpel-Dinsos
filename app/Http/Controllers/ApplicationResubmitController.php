<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Jobs\SendApplicationNotificationJob;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationLog;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Perbaikan pengajuan yang DIKEMBALIKAN (status: returned).
 *
 * Melengkapi siklus SOP: ajukan → verifikasi → dikembalikan → PERBAIKI →
 * kirim ulang → verifikasi. Hanya pemilik pengajuan yang boleh memperbaiki,
 * dan hanya saat status = returned.
 */
class ApplicationResubmitController extends Controller
{
    public function edit(string $code)
    {
        $application = $this->resolveOwnedReturned($code);
        $application->load(['serviceType', 'documents']);

        $returnReason = $application->logs()
            ->where('action', 'returned')
            ->latest('id')
            ->value('notes');

        return view('public.applications.resubmit', compact('application', 'returnReason'));
    }

    public function update(Request $request, string $code)
    {
        $application = $this->resolveOwnedReturned($code);
        $application->load(['serviceType', 'documents']);

        $data = $request->validate([
            'beneficiary_name' => 'required|string|max:150',
            'beneficiary_nik' => 'nullable|string|size:16',
            'beneficiary_relation' => 'required|string|in:diri_sendiri,anggota_keluarga,kuasa',
            'purpose' => 'nullable|string|max:1000',
            'replace_docs' => 'array',
            'replace_docs.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'new_docs' => 'array',
            'new_docs.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'new_doc_labels' => 'array',
        ]);

        $replaceFiles = $request->file('replace_docs', []);
        $newFiles = $request->file('new_docs', []);
        $newLabels = $request->input('new_doc_labels', []);

        // Berkas yang ditandai bermasalah (is_validated === false) WAJIB diunggah ulang.
        $flagged = $application->documents->filter(fn ($d) => $d->is_validated === false);
        $missing = $flagged->filter(fn ($d) => empty($replaceFiles[$d->id]));
        if ($flagged->isNotEmpty() && $missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'replace_docs' => 'Mohon unggah ulang semua berkas yang ditandai perlu diperbaiki: '
                    .$missing->pluck('label')->implode(', ').'.',
            ]);
        }

        // Tanpa berkas bermasalah → tetap butuh minimal 1 perubahan agar tidak loop kosong.
        $anyReplace = collect($replaceFiles)->filter()->isNotEmpty();
        $anyNew = collect($newFiles)->filter()->isNotEmpty();
        $dataChanged = $application->beneficiary_name !== $data['beneficiary_name']
            || (string) $application->beneficiary_nik !== (string) ($data['beneficiary_nik'] ?? '')
            || $application->beneficiary_relation !== $data['beneficiary_relation']
            || (string) $application->purpose !== (string) ($data['purpose'] ?? '');
        if ($flagged->isEmpty() && ! $anyReplace && ! $anyNew && ! $dataChanged) {
            throw ValidationException::withMessages([
                'replace_docs' => 'Belum ada perubahan. Ganti minimal satu berkas atau perbaiki data sebelum kirim ulang.',
            ]);
        }

        DB::transaction(function () use ($application, $data, $replaceFiles, $newFiles, $newLabels) {
            $application->update([
                'beneficiary_name' => $data['beneficiary_name'],
                'beneficiary_nik' => $data['beneficiary_nik'] ?? null,
                'beneficiary_relation' => $data['beneficiary_relation'],
                'purpose' => $data['purpose'] ?? null,
            ]);

            // Ganti berkas (hapus file lama di disk secure → simpan baru → reset validasi).
            foreach ($replaceFiles as $docId => $file) {
                if (! $file) continue;
                $doc = $application->documents->firstWhere('id', (int) $docId);
                if (! $doc) continue;
                if ($doc->file_path) {
                    Storage::disk('secure')->delete($doc->file_path);
                }
                $path = $file->store('applications/'.$application->id, 'secure');
                $doc->update([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_validated' => null,
                    'notes' => null,
                ]);
            }

            // Berkas tambahan baru.
            foreach ($newFiles as $key => $file) {
                if (! $file) continue;
                $path = $file->store('applications/'.$application->id, 'secure');
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'type' => 'tambahan',
                    'label' => $newLabels[$key] ?? $file->getClientOriginalName(),
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }

            // Kembali ke antrian verifikasi + reset SLA dari sekarang (submitted_at tetap untuk arsip).
            $from = $application->status?->value ?? $application->status;
            $slaMinutes = $application->serviceType->sla_minutes ?? 1440;
            $application->update([
                'status' => ApplicationStatus::Submitted->value,
                'current_step' => 'verifikasi_loket',
                'sla_due_at' => now()->addMinutes($slaMinutes),
            ]);

            ApplicationLog::create([
                'application_id' => $application->id,
                'user_id' => auth()->id(),
                'action' => 'resubmitted',
                'from_status' => $from,
                'to_status' => ApplicationStatus::Submitted->value,
                'notes' => 'Pemohon memperbaiki berkas & mengirim ulang pengajuan.',
            ]);
        });

        SendApplicationNotificationJob::dispatch($application->id, 'status');

        return redirect()->route('cek-status.index', ['code' => $application->code])
            ->with('success', 'Pengajuan '.$application->code.' berhasil dikirim ulang dan akan diverifikasi kembali.');
    }

    /**
     * Pastikan pengajuan milik user yang login DAN berstatus returned.
     */
    protected function resolveOwnedReturned(string $code): Application
    {
        $application = Application::where('code', $code)->firstOrFail();

        if ($application->applicant_user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke pengajuan ini.');
        }

        if (($application->status?->value ?? $application->status) !== ApplicationStatus::Returned->value) {
            throw new HttpResponseException(
                redirect()->route('cek-status.index', ['code' => $application->code])
                    ->with('info', 'Pengajuan ini tidak sedang dalam status dikembalikan.')
            );
        }

        return $application;
    }
}
