<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\OutputDocument;
use App\Services\NotificationGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job notifikasi outbound untuk pengajuan layanan.
 *
 * Type:
 *   - submitted  : pemohon kirim pengajuan baru
 *   - completed  : surat terbit (perlu OutputDocument)
 *   - survey     : undangan SKM (setelah selesai)
 *   - status     : perubahan status (ditolak/dikembalikan/diproses), $note = catatan petugas
 */
class SendApplicationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $applicationId,
        public string $type,
        public ?int $documentId = null,
        public ?string $note = null,
    ) {}

    public function handle(NotificationGateway $gateway): void
    {
        $app = Application::with('applicant', 'serviceType')->find($this->applicationId);
        if (! $app) return;

        match ($this->type) {
            'submitted' => $gateway->sendApplicationSubmitted($app),
            'completed' => $this->documentId
                ? $gateway->sendApplicationCompleted($app, OutputDocument::find($this->documentId))
                : null,
            'survey'    => $gateway->sendSurveyInvitation($app),
            'status'    => $gateway->sendApplicationStatusUpdate($app, $this->note),
            default     => null,
        };
    }
}
