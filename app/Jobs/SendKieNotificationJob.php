<?php

namespace App\Jobs;

use App\Models\KieConsultation;
use App\Services\NotificationGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Kirim konfirmasi WA pendaftaran Konsultasi Warga (KIE) via queue worker,
 * konsisten dengan SendApplicationNotificationJob (robust di PHP-FPM).
 */
class SendKieNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public int $kieId) {}

    public function handle(NotificationGateway $gateway): void
    {
        $kie = KieConsultation::find($this->kieId);
        if (! $kie) {
            return;
        }

        if ($gateway->sendKieRegistration($kie)) {
            $kie->forceFill(['notified_at' => now()])->saveQuietly();
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('[SendKieNotificationJob] Gagal KIE #'.$this->kieId.': '.$e->getMessage());
    }
}
