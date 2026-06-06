<?php

namespace App\Jobs;

use App\Services\NotificationGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job kirim OTP via WhatsApp/Email.
 *
 * Di-dispatch dari WargaAuthController + AuthApiController.
 * Diproses oleh queue worker (service `queue` di Railway).
 *
 * Tidak boleh di-defer via afterResponse karena di environment PHP-FPM
 * di balik nginx, fastcgi_finish_request kadang tidak fire reliable.
 */
class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public string $contact,
        public string $code,
    ) {}

    public function handle(NotificationGateway $gateway): void
    {
        $gateway->sendOtp($this->contact, $this->code);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('[SendOtpJob] Gagal kirim OTP ke '.$this->contact.': '.$e->getMessage());
    }
}
