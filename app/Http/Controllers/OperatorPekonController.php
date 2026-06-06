<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Jobs\SendApplicationNotificationJob;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationLog;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperatorPekonController extends Controller
{
    use HandlesDocumentUploads;

    public function dashboard(Request $request)
    {
        $operator = Auth::user();
        $services = ServiceType::active()->orderBy('order_no')->get();
        $recent = Application::whereHas('applicant', fn ($q) => $q->where('pekon_id', $operator->pekon_id))
            ->with('serviceType', 'applicant')
            ->latest()
            ->take(15)
            ->get();

        $stats = [
            'submitted' => Application::whereHas('applicant', fn ($q) => $q->where('pekon_id', $operator->pekon_id))->count(),
            'in_progress' => Application::whereHas('applicant', fn ($q) => $q->where('pekon_id', $operator->pekon_id))
                ->whereNotIn('status', ['completed', 'rejected'])
                ->count(),
            'completed' => Application::whereHas('applicant', fn ($q) => $q->where('pekon_id', $operator->pekon_id))
                ->where('status', 'completed')
                ->count(),
        ];

        return view('public.pekon.dashboard', compact('operator', 'services', 'recent', 'stats'));
    }

    public function createApplication(Request $request)
    {
        $services = ServiceType::active()->orderBy('order_no')->get();
        $service = null;
        if ($request->filled('service')) {
            $service = ServiceType::active()->where('slug', $request->input('service'))->first();
        }

        return view('public.pekon.create', compact('services', 'service'));
    }

    public function storeApplication(Request $request)
    {
        $operator = Auth::user();

        $this->guardOversizedUploads($request);

        $data = $request->validate([
            'service_slug' => 'required|exists:service_types,slug',
            'beneficiary_name' => 'required|string|max:150',
            'beneficiary_nik' => 'required|string|size:16',
            'beneficiary_phone' => 'required|string|max:20',
            'purpose' => 'nullable|string|max:1000',
            'docs.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'pin' => 'required|string', // PIN e-sign Kepala Pekon
            'consent' => 'required|accepted',
        ], $this->documentUploadMessages());

        // PIN e-sign Kepala Pekon — diambil dari konfigurasi (ESIGN_PEKON_PIN),
        // BUKAN hardcoded. Fail-closed bila belum dikonfigurasi.
        $expectedPin = (string) config('services.esign.pekon_pin');
        if ($expectedPin === '' || ! hash_equals($expectedPin, (string) $data['pin'])) {
            return back()->withErrors(['pin' => 'PIN e-sign tidak cocok atau belum dikonfigurasi.'])->withInput();
        }

        $service = ServiceType::where('slug', $data['service_slug'])->firstOrFail();

        $application = DB::transaction(function () use ($request, $service, $data, $operator) {
            // Cari warga berdasarkan NIK (lewat hash) atau buat baru
            $warga = User::findByNik($data['beneficiary_nik']);
            if (! $warga) {
                $warga = User::create([
                    'nik' => $data['beneficiary_nik'],
                    'name' => $data['beneficiary_name'],
                    'phone' => $data['beneficiary_phone'],
                    'email' => 'warga'.substr($data['beneficiary_nik'], -8).'@warga.test',
                    // bcrypt rounds=4 cukup karena warga login via OTP, bukan password
                    'password' => Hash::make(str()->random(32), ['rounds' => 4]),
                    'role' => UserRole::Warga->value,
                    'kecamatan_id' => $operator->kecamatan_id,
                    'pekon_id' => $operator->pekon_id,
                    'is_active' => true,
                ]);
            }

            // Retry kalau race condition pada generateCode
            $application = null;
            $attempts = 0;
            do {
                $attempts++;
                try {
                    $application = Application::create([
                        'code' => Application::generateCode($service),
                        'service_type_id' => $service->id,
                        'applicant_user_id' => $warga->id,
                        'beneficiary_name' => $data['beneficiary_name'],
                        'beneficiary_nik' => $data['beneficiary_nik'],
                        'beneficiary_relation' => 'diri_sendiri',
                        'purpose' => $data['purpose'] ?? null,
                        'status' => ApplicationStatus::Submitted->value,
                        'current_step' => 'verifikasi_loket',
                        'submitted_at' => now(),
                        'meta' => ['submitted_by_operator' => $operator->id, 'esign_pin_verified' => true],
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    if ($attempts >= 3) {
                        throw $e;
                    }
                    usleep(50000);
                }
            } while ($attempts < 3);
            $application->update(['sla_due_at' => $application->calculateSlaDueAt()]);

            foreach ($request->file('docs', []) as $idx => $file) {
                // Berkas sensitif (KTP/KK) WAJIB ke disk 'secure', konsisten dgn jalur warga.
                $path = $file->store('applications/'.$application->id, 'secure');
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'type' => 'berkas_'.$idx,
                    'label' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }

            QueueTicket::createNext([
                'application_id' => $application->id,
                'status' => 'waiting',
            ], 'P');

            ApplicationLog::create([
                'application_id' => $application->id,
                'user_id' => $operator->id,
                'action' => 'created',
                'to_status' => ApplicationStatus::Submitted->value,
                'notes' => 'Diajukan oleh Operator Pekon '.$operator->pekon?->name.' (e-sign PIN diverifikasi).',
            ]);

            return $application;
        });

        // Push ke queue worker
        SendApplicationNotificationJob::dispatch($application->id, 'submitted');

        return redirect()->route('pekon.dashboard')->with('success',
            'Pengajuan '.$application->code.' berhasil diajukan atas nama '.$application->beneficiary_name);
    }
}
