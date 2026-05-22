<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationLog;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function create(string $slug)
    {
        $service = ServiceType::active()->where('slug', $slug)->firstOrFail();
        return view('public.applications.create', compact('service'));
    }

    public function store(Request $request, string $slug)
    {
        $service = ServiceType::active()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'beneficiary_name' => 'required|string|max:150',
            'beneficiary_nik' => 'nullable|string|size:16',
            'beneficiary_relation' => 'required|string|in:diri_sendiri,anggota_keluarga,kuasa',
            'applicant_name' => 'required|string|max:150',
            'applicant_phone' => 'required|string|max:20',
            'applicant_email' => 'nullable|email|max:150',
            'purpose' => 'nullable|string|max:1000',
            'docs.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'consent' => 'required|accepted',
        ]);

        $application = DB::transaction(function () use ($request, $service, $data) {
            $applicant = $this->resolveOrCreateApplicant($data);

            // Retry sampai 3x kalau race condition pada generateCode (UNIQUE violation)
            $application = null;
            $attempts = 0;
            do {
                $attempts++;
                try {
                    $application = Application::create([
                        'code' => Application::generateCode($service),
                        'service_type_id' => $service->id,
                        'applicant_user_id' => $applicant->id,
                        'beneficiary_name' => $data['beneficiary_name'],
                        'beneficiary_nik' => $data['beneficiary_nik'] ?? null,
                        'beneficiary_relation' => $data['beneficiary_relation'],
                        'purpose' => $data['purpose'] ?? null,
                        'status' => ApplicationStatus::Submitted->value,
                        'current_step' => 'verifikasi_loket',
                        'priority' => 'normal',
                        'submitted_at' => now(),
                    ]);
                    break;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    if ($attempts >= 3) {
                        throw $e;
                    }
                    usleep(50000); // 50ms jitter
                }
            } while ($attempts < 3);

            $application->update(['sla_due_at' => $application->calculateSlaDueAt()]);

            // Simpan berkas
            $docTypes = $request->input('doc_types', []);
            $docLabels = $request->input('doc_labels', []);
            $files = $request->file('docs', []);

            foreach ($files as $key => $file) {
                // Berkas sensitif (KTP/KK/foto PPKS) wajib ke disk 'secure'.
                // Disk 'secure' default ke local terisolasi, dapat di-switch ke MinIO
                // dengan SECURE_DISK_DRIVER=minio di .env tanpa ubah kode.
                $path = $file->store('applications/'.$application->id, 'secure');
                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'type' => $docTypes[$key] ?? 'other',
                    'label' => $docLabels[$key] ?? $file->getClientOriginalName(),
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }

            // Generate tiket antrian
            QueueTicket::create([
                'application_id' => $application->id,
                'ticket_number' => QueueTicket::nextNumber('A'),
                'ticket_date' => today(),
                'priority' => 'normal',
                'status' => 'waiting',
            ]);

            // Log
            ApplicationLog::create([
                'application_id' => $application->id,
                'user_id' => $applicant->id,
                'action' => 'created',
                'to_status' => ApplicationStatus::Submitted->value,
                'notes' => 'Pengajuan dikirim oleh pemohon.',
            ]);

            return $application;
        });

        // Notifikasi outbound (WA/Email) — defer setelah HTTP response dikirim
        // agar user tidak menunggu I/O outbound (log file / WA gateway).
        $appId = $application->id;
        dispatch(function () use ($appId) {
            try {
                $app = Application::with('applicant', 'serviceType')->find($appId);
                if ($app) {
                    app(\App\Services\NotificationGateway::class)->sendApplicationSubmitted($app);
                }
            } catch (\Throwable $e) {
                \Log::warning('Notif submit gagal: '.$e->getMessage());
            }
        })->afterResponse();

        return redirect()->route('pengajuan.sukses', ['code' => $application->code]);
    }

    public function success(string $code)
    {
        $application = Application::with(['serviceType', 'queueTicket'])
            ->where('code', $code)
            ->firstOrFail();

        return view('public.applications.success', compact('application'));
    }

    /**
     * Resolve user pemohon dengan urutan:
     *  1. Kalau sudah login → pakai akun login (TIDAK buat user baru)
     *  2. Cari by phone (normalized: 08xxx → 628xxx)
     *  3. Cari by email (kalau form isi email)
     *  4. Buat akun warga baru kalau benar-benar tidak ditemukan
     *
     * Mencegah UNIQUE collision phone/email saat warga sudah punya akun
     * tapi mengisi form dengan format nomor berbeda.
     */
    protected function resolveOrCreateApplicant(array $data): \App\Models\User
    {
        // 1. Sudah login → pakai akun yang ada
        if ($user = auth()->user()) {
            // Update profil yang kosong dari data form (best-effort, non-destructive)
            $updates = [];
            if (! $user->name && ! empty($data['applicant_name'])) $updates['name'] = $data['applicant_name'];
            if (! $user->email && ! empty($data['applicant_email'])) $updates['email'] = $data['applicant_email'];
            if ($updates) $user->update($updates);
            return $user;
        }

        $phone = $this->normalizePhone($data['applicant_phone'] ?? '');
        $email = ! empty($data['applicant_email']) ? strtolower(trim($data['applicant_email'])) : null;

        // 2. Cari by phone (normalized)
        if ($phone) {
            $user = \App\Models\User::where('phone', $phone)->first();
            if ($user) {
                if (! $user->email && $email) $user->update(['email' => $email]);
                return $user;
            }
        }

        // 3. Cari by email (case kalau user pernah daftar pakai email lalu submit pakai HP)
        if ($email) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                if (! $user->phone && $phone) $user->update(['phone' => $phone]);
                return $user;
            }
        }

        // 4. Buat akun baru
        return \App\Models\User::create([
            'phone' => $phone ?: null,
            'name' => $data['applicant_name'] ?? 'Warga '.($phone ? substr($phone, -4) : substr(uniqid(), -4)),
            'email' => $email ?: ('warga'.substr(uniqid(), -8).'@warga.test'),
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32), ['rounds' => 4]),
            'role' => \App\Enums\UserRole::Warga->value,
            'is_active' => true,
        ]);
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (! $phone) return null;
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) return '628'.substr($phone, 2);
        if (str_starts_with($phone, '+62')) return substr($phone, 1);
        if (str_starts_with($phone, '8')) return '62'.$phone;
        return $phone;
    }
}
