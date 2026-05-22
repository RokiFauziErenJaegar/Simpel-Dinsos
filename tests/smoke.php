<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLog;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use App\Models\User;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = ServiceType::where('slug', 'surat-keterangan-dtsen')->firstOrFail();
$user = User::where('email', 'budi@warga.test')->firstOrFail();

$app = Application::create([
    'code' => Application::generateCode($service),
    'service_type_id' => $service->id,
    'applicant_user_id' => $user->id,
    'beneficiary_name' => 'Budi Santoso',
    'beneficiary_nik' => '1871030000000001',
    'beneficiary_relation' => 'diri_sendiri',
    'purpose' => 'Untuk pendaftaran beasiswa anak',
    'status' => ApplicationStatus::Submitted->value,
    'current_step' => 'verifikasi_loket',
    'priority' => 'normal',
    'submitted_at' => now(),
]);
$app->update(['sla_due_at' => $app->calculateSlaDueAt()]);

QueueTicket::create([
    'application_id' => $app->id,
    'ticket_number' => 'A-001',
    'ticket_date' => today()->toDateString(),
    'priority' => 'normal',
    'status' => 'waiting',
]);

ApplicationLog::create([
    'application_id' => $app->id,
    'user_id' => $user->id,
    'action' => 'created',
    'to_status' => 'submitted',
    'notes' => 'Pengajuan dikirim oleh pemohon (smoke test).',
]);

// Buat satu lagi dengan status di tengah workflow
$app2 = Application::create([
    'code' => Application::generateCode($service),
    'service_type_id' => $service->id,
    'applicant_user_id' => $user->id,
    'beneficiary_name' => 'Siti Maesaroh',
    'beneficiary_relation' => 'anggota_keluarga',
    'status' => ApplicationStatus::InProcess->value,
    'current_step' => 'pemrosesan',
    'submitted_at' => now()->subHours(2),
    'sla_due_at' => now()->addMinutes(13),
    'priority' => 'normal',
]);
QueueTicket::create([
    'application_id' => $app2->id,
    'ticket_number' => 'A-002',
    'ticket_date' => today()->toDateString(),
    'priority' => 'normal',
    'status' => 'serving',
    'counter' => 'LOKET 1',
    'called_at' => now()->subMinutes(5),
    'served_at' => now()->subMinutes(5),
]);
foreach ([
    ['created', 'submitted', 'Pengajuan diajukan'],
    ['verified', 'in_verification', 'Berkas diverifikasi'],
    ['disposed', 'in_process', 'Disposisi ke Petugas Loket'],
] as [$action, $to, $notes]) {
    ApplicationLog::create([
        'application_id' => $app2->id,
        'user_id' => $user->id,
        'action' => $action,
        'to_status' => $to,
        'notes' => $notes,
    ]);
}

// Buat satu yang selesai
$app3 = Application::create([
    'code' => Application::generateCode($service),
    'service_type_id' => $service->id,
    'applicant_user_id' => $user->id,
    'beneficiary_name' => 'Pak Suparmin',
    'beneficiary_relation' => 'diri_sendiri',
    'status' => ApplicationStatus::Completed->value,
    'current_step' => 'selesai',
    'submitted_at' => now()->subDay(),
    'sla_due_at' => now()->subDay()->addMinutes(15),
    'completed_at' => now()->subDay()->addMinutes(12),
    'priority' => 'normal',
]);
QueueTicket::create([
    'application_id' => $app3->id,
    'ticket_number' => 'A-101',
    'ticket_date' => today()->subDay()->toDateString(),
    'priority' => 'normal',
    'status' => 'done',
    'counter' => 'LOKET 1',
    'called_at' => now()->subDay(),
    'served_at' => now()->subDay(),
    'done_at' => now()->subDay()->addMinutes(12),
]);

echo "OK: app1={$app->code}, app2={$app2->code}, app3={$app3->code}\n";
echo "Tickets today: ".QueueTicket::whereDate('ticket_date', today())->count()."\n";
echo "Apps total: ".Application::count()."\n";
