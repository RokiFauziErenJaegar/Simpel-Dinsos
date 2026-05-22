<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationLog;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OperatorPekonController extends Controller
{
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
        $data = $request->validate([
            'service_slug' => 'required|exists:service_types,slug',
            'beneficiary_name' => 'required|string|max:150',
            'beneficiary_nik' => 'required|string|size:16',
            'beneficiary_phone' => 'required|string|max:20',
            'purpose' => 'nullable|string|max:1000',
            'docs.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'pin' => 'required|string', // PIN e-sign Kepala Pekon
            'consent' => 'required|accepted',
        ]);

        // Validasi PIN sederhana — di produksi pakai hash Pekon
        if ($data['pin'] !== '123456') {
            return back()->withErrors(['pin' => 'PIN e-sign tidak cocok.'])->withInput();
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
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    if ($attempts >= 3) throw $e;
                    usleep(50000);
                }
            } while ($attempts < 3);
            $application->update(['sla_due_at' => $application->calculateSlaDueAt()]);

            foreach ($request->file('docs', []) as $idx => $file) {
                $path = $file->store('applications/'.$application->id, 'public');
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

            QueueTicket::create([
                'application_id' => $application->id,
                'ticket_number' => $this->generateTicketNumber('P'),
                'ticket_date' => today()->toDateString(),
                'status' => 'waiting',
            ]);

            ApplicationLog::create([
                'application_id' => $application->id,
                'user_id' => $operator->id,
                'action' => 'created',
                'to_status' => ApplicationStatus::Submitted->value,
                'notes' => 'Diajukan oleh Operator Pekon '.$operator->pekon?->name.' (e-sign PIN diverifikasi).',
            ]);

            return $application;
        });

        // Defer notif outbound setelah response untuk performance
        $appId = $application->id;
        dispatch(function () use ($appId) {
            try {
                $a = Application::with('applicant', 'serviceType')->find($appId);
                if ($a) app(\App\Services\NotificationGateway::class)->sendApplicationSubmitted($a);
            } catch (\Throwable $e) {
                \Log::warning('Notif gagal: '.$e->getMessage());
            }
        })->afterResponse();

        return redirect()->route('pekon.dashboard')->with('success',
            'Pengajuan '.$application->code.' berhasil diajukan atas nama '.$application->beneficiary_name);
    }

    protected function generateTicketNumber(string $prefix): string
    {
        $count = QueueTicket::whereDate('ticket_date', today())
            ->where('ticket_number', 'like', $prefix.'-%')
            ->count() + 1;
        return sprintf('%s-%03d', $prefix, $count);
    }
}
