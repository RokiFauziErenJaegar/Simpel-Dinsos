<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\OutputDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Adapter pengiriman notifikasi outbound (WA / Email / SMS).
 *
 * Driver yang didukung:
 * - log     : tulis ke file storage (default, dev/demo)
 * - fonnte  : WhatsApp via api.fonnte.com (gratis 100 pesan/hari)
 * - wablas  : WhatsApp via wablas.com (trial terbatas)
 * - cloud   : WhatsApp Cloud API resmi Meta (gratis 1000 conversation/bulan)
 * - email   : Email via SMTP Laravel Mail
 *
 * Set NOTIFICATION_DRIVER di .env untuk pilih driver utama.
 * Set NOTIFICATION_FALLBACK_EMAIL=true agar otomatis kirim email kalau WA gagal.
 *
 * Antar-muka publik (sendOtp, sendApplicationSubmitted, dst) tidak berubah —
 * pemanggil tidak perlu peduli driver mana yang aktif.
 */
class NotificationGateway
{
    protected string $driver;

    protected bool $fallbackEmail;

    public function __construct()
    {
        $this->driver = config('services.notifications.driver', 'log');
        $this->fallbackEmail = (bool) config('services.notifications.fallback_email', false);
    }

    // ============================================================
    // Public message senders
    // ============================================================

    public function sendApplicationCompleted(Application $application, OutputDocument $document): void
    {
        $applicant = $application->applicant;
        if (! $applicant || ! $applicant->phone) {
            return;
        }

        $verifyUrl = route('document.verify', ['token' => $document->verification_token]);
        $message = trim(<<<TXT
Halo {$applicant->name},

Pengajuan Anda {$application->code} ({$application->serviceType->name})
telah SELESAI diproses.

Nomor Surat: {$document->document_number}
Unduh / verifikasi: {$verifyUrl}

— Dinas Sosial Pringsewu
TXT);

        $this->dispatchTo($applicant, $message, 'Surat Selesai · '.$application->code);
    }

    public function sendApplicationSubmitted(Application $application): void
    {
        $applicant = $application->applicant;
        if (! $applicant || ! $applicant->phone) {
            return;
        }

        $statusUrl = route('cek-status.index', ['code' => $application->code]);
        $message = trim(<<<TXT
Halo {$applicant->name},

Pengajuan {$application->code} telah kami terima.
Layanan: {$application->serviceType->name}
Penerima: {$application->beneficiary_name}

Pantau status: {$statusUrl}
Estimasi selesai: {$application->sla_due_at?->translatedFormat('d M H:i')}

— Dinas Sosial Pringsewu
TXT);

        $this->dispatchTo($applicant, $message, 'Pengajuan diterima · '.$application->code);
    }

    /**
     * Kirim notifikasi perubahan status pengajuan (ditolak / dikembalikan /
     * sedang diproses, dst). Label status diambil dari status terkini
     * pengajuan, sehingga satu metode ini melayani semua transisi.
     *
     * $note = catatan petugas (alasan penolakan / alasan dikembalikan).
     */
    public function sendApplicationStatusUpdate(Application $application, ?string $note = null): void
    {
        $applicant = $application->applicant;
        if (! $applicant || ! $applicant->phone) {
            return;
        }

        $status = $application->status instanceof ApplicationStatus
            ? $application->status
            : ApplicationStatus::tryFrom((string) $application->status);
        $statusLabel = $status?->label() ?? (string) $application->status;

        $statusUrl = route('cek-status.index', ['code' => $application->code]);
        $noteLine = $note ? "Catatan: {$note}\n\n" : '';

        $message = trim(<<<TXT
Halo {$applicant->name},

Status pengajuan {$application->code} ({$application->serviceType->name})
diperbarui menjadi: *{$statusLabel}*.

{$noteLine}Cek detail: {$statusUrl}

— Dinas Sosial Pringsewu
TXT);

        $this->dispatchTo($applicant, $message, 'Update Pengajuan · '.$application->code);
    }

    /**
     * Kirim OTP. $contact bisa nomor WA atau email.
     * Sistem otomatis pilih channel berdasarkan format kontak.
     *
     * - Email → selalu via Mail::raw (Laravel SMTP) — kecuali NOTIFICATION_DRIVER=log
     * - HP → via driver yang dikonfigurasi (fonnte/wablas/cloud/log)
     */
    public function sendOtp(string $contact, string $code): void
    {
        $message = "Kode OTP SIMPEL DINSOS: *{$code}*\nBerlaku 5 menit. Jangan bagikan ke siapa pun.\n\n— Dinsos Pringsewu";

        if ($this->isEmail($contact)) {
            // Mode demo: simpan ke outbox file biar mudah dilihat tanpa SMTP setup
            if ($this->driver === 'log') {
                $this->sendViaLog('email', $contact, $message);

                return;
            }
            $this->sendViaEmail($contact, 'Kode OTP SIMPEL DINSOS', $message);

            return;
        }

        // Asumsi nomor WA
        $this->dispatch('whatsapp', $contact, $message);
    }

    public function sendSurveyInvitation(Application $application): void
    {
        $applicant = $application->applicant;
        if (! $applicant || ! $applicant->phone) {
            return;
        }

        $surveyUrl = route('skm.create', ['code' => $application->code]);
        $message = trim(<<<TXT
Halo {$applicant->name},

Terima kasih telah menggunakan layanan kami ({$application->serviceType->name}).
Mohon luangkan 1 menit untuk menilai layanan kami:

{$surveyUrl}

— Dinas Sosial Pringsewu
TXT);

        $this->dispatchTo($applicant, $message, 'Survei Kepuasan · '.$application->code);
    }

    // ============================================================
    // Dispatch logic
    // ============================================================

    /**
     * Kirim ke applicant (User model) — coba WA dulu, fallback email jika dikonfigurasi.
     */
    protected function dispatchTo($user, string $message, string $emailSubject = 'Notifikasi Dinsos'): void
    {
        $ok = $this->dispatch('whatsapp', $user->phone, $message, [
            'fallback_email' => $this->fallbackEmail ? $user->email : null,
            'email_subject' => $emailSubject,
        ]);

        if (! $ok && $this->fallbackEmail && $user->email && ! $this->isFakeEmail($user->email)) {
            $this->sendViaEmail($user->email, $emailSubject, $message);
        }
    }

    /**
     * Kirim ke channel. Return true kalau sukses, false kalau gagal (fallback bisa kick in).
     */
    protected function dispatch(string $channel, string $to, string $message, array $opts = []): bool
    {
        if (! $to) {
            return false;
        }

        return match ($this->driver) {
            'fonnte' => $this->sendViaFonnte($to, $message),
            'wablas' => $this->sendViaWablas($to, $message),
            'cloud' => $this->sendViaCloudApi($to, $message),
            'email' => $this->sendViaEmail($to, $opts['email_subject'] ?? 'Notifikasi', $message),
            default => $this->sendViaLog($channel, $to, $message),
        };
    }

    // ============================================================
    // Driver implementations
    // ============================================================

    /**
     * Driver: log — tulis ke storage (untuk demo / development).
     */
    protected function sendViaLog(string $channel, string $to, string $message): bool
    {
        $entry = [
            'driver' => $this->driver,
            'channel' => $channel,
            'to' => $to,
            'sent_at' => now()->toIso8601String(),
            'message' => $message,
        ];
        Log::channel('single')->info('[OUTBOUND] '.json_encode($entry, JSON_UNESCAPED_UNICODE));

        $outbox = 'outbox/'.now()->format('Y-m-d').'.log';
        $line = sprintf("[%s] %s → %s\n%s\n%s\n",
            now()->format('H:i:s'),
            strtoupper($channel),
            $to,
            str_repeat('-', 40),
            $message
        );
        Storage::disk('local')->append($outbox, $line."\n");

        return true;
    }

    /**
     * Driver: Fonnte (Indonesia) — gratis 100 pesan/hari di paket Hobby.
     * https://fonnte.com — daftar → scan QR → ambil token di dashboard
     *
     * Setup .env:
     *   NOTIFICATION_DRIVER=fonnte
     *   FONNTE_TOKEN=xxxxxxxxxxxxxxx
     */
    protected function sendViaFonnte(string $to, string $message): bool
    {
        $token = config('services.notifications.fonnte_token');
        if (! $token) {
            Log::warning('[FONNTE] FONNTE_TOKEN belum di-set di .env — fallback ke log.');

            return $this->sendViaLog('whatsapp', $to, $message);
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->connectTimeout(2)
                ->timeout(5)
                ->retry(1, 300) // 1x retry, delay 300ms — kalau >5s memang lemot, jangan tunggu lagi
                ->asMultipart()
                ->post('https://api.fonnte.com/send', [
                    [
                        'name' => 'target',
                        'contents' => $this->normalizePhoneFonnte($to),
                    ],
                    [
                        'name' => 'message',
                        'contents' => $message,
                    ],
                    [
                        'name' => 'countryCode',
                        'contents' => '62',
                    ],
                ]);

            $data = $response->json() ?? [];
            $ok = ($response->successful() && ($data['status'] ?? false) === true);

            if ($ok) {
                Log::info('[FONNTE] Berhasil kirim', ['to' => $to, 'id' => $data['id'] ?? null]);
            } else {
                Log::warning('[FONNTE] Gagal kirim', [
                    'to' => $to,
                    'status_code' => $response->status(),
                    'response' => $data,
                ]);
                // Tetap log ke outbox sebagai backup record
                $this->sendViaLog('whatsapp-fonnte-fail', $to, $message);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[FONNTE] Exception: '.$e->getMessage(), ['to' => $to]);
            $this->sendViaLog('whatsapp-fonnte-error', $to, $message);

            return false;
        }
    }

    /**
     * Driver: Wablas (Indonesia). Pakai endpoint v2.
     * https://wablas.com — trial gratis terbatas.
     *
     * Setup .env:
     *   NOTIFICATION_DRIVER=wablas
     *   WABLAS_TOKEN=xxxxxxxxxx
     *   WABLAS_DOMAIN=https://solo.wablas.com (sesuaikan dengan domain dashboard Wablas Anda)
     */
    protected function sendViaWablas(string $to, string $message): bool
    {
        $token = config('services.notifications.wablas_token');
        $domain = config('services.notifications.wablas_domain', 'https://solo.wablas.com');
        if (! $token) {
            Log::warning('[WABLAS] WABLAS_TOKEN belum di-set, fallback ke log.');

            return $this->sendViaLog('whatsapp', $to, $message);
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->connectTimeout(2)
                ->timeout(5)
                ->retry(1, 300)
                ->asForm()
                ->post(rtrim($domain, '/').'/api/send-message', [
                    'phone' => $this->normalizePhoneFonnte($to),
                    'message' => $message,
                ]);

            $data = $response->json() ?? [];
            $ok = $response->successful() && ($data['status'] ?? false) === true;

            if ($ok) {
                Log::info('[WABLAS] Berhasil', ['to' => $to]);
            } else {
                Log::warning('[WABLAS] Gagal', ['to' => $to, 'response' => $data]);
                $this->sendViaLog('whatsapp-wablas-fail', $to, $message);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[WABLAS] Exception: '.$e->getMessage());
            $this->sendViaLog('whatsapp-wablas-error', $to, $message);

            return false;
        }
    }

    /**
     * Driver: WhatsApp Cloud API resmi Meta. GRATIS 1000 conversation/bulan.
     * https://developers.facebook.com/docs/whatsapp/cloud-api
     *
     * Setup:
     * 1. Buat akun Meta for Developers + Business Manager
     * 2. Verifikasi WhatsApp Business Account
     * 3. Setup phone number + dapat WHATSAPP_TOKEN + WHATSAPP_PHONE_ID
     * 4. Approve template message untuk OTP
     *
     * Setup .env:
     *   NOTIFICATION_DRIVER=cloud
     *   WHATSAPP_TOKEN=EAA...
     *   WHATSAPP_PHONE_ID=12345...
     *   WHATSAPP_TEMPLATE_OTP=otp_simpel_dinsos
     */
    protected function sendViaCloudApi(string $to, string $message): bool
    {
        $token = config('services.notifications.whatsapp_token');
        $phoneId = config('services.notifications.whatsapp_phone_id');
        if (! $token || ! $phoneId) {
            Log::warning('[CLOUD] WHATSAPP_TOKEN/PHONE_ID belum di-set, fallback log.');

            return $this->sendViaLog('whatsapp', $to, $message);
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalizePhoneFonnte($to),
                'type' => 'text',
                'text' => ['preview_url' => true, 'body' => $message],
            ];

            $response = Http::withToken($token)
                ->connectTimeout(2)
                ->timeout(6)
                ->retry(1, 300)
                ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", $payload);

            $ok = $response->successful();
            if ($ok) {
                Log::info('[CLOUD] Berhasil', ['to' => $to]);
            } else {
                Log::warning('[CLOUD] Gagal', ['to' => $to, 'response' => $response->json()]);
                $this->sendViaLog('whatsapp-cloud-fail', $to, $message);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::error('[CLOUD] Exception: '.$e->getMessage());
            $this->sendViaLog('whatsapp-cloud-error', $to, $message);

            return false;
        }
    }

    /**
     * Driver: Email via SMTP Laravel Mail (built-in, gratis dengan SMTP gratis seperti Gmail).
     *
     * Setup .env (contoh Gmail):
     *   MAIL_MAILER=smtp
     *   MAIL_HOST=smtp.gmail.com
     *   MAIL_PORT=587
     *   MAIL_USERNAME=pringsewudinsos@gmail.com
     *   MAIL_PASSWORD=app-specific-password
     *   MAIL_ENCRYPTION=tls
     *   MAIL_FROM_ADDRESS=pringsewudinsos@gmail.com
     */
    protected function sendViaEmail(string $to, string $subject, string $body): bool
    {
        if (! $this->isEmail($to) || $this->isFakeEmail($to)) {
            return false;
        }

        try {
            Mail::raw($body, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });
            Log::info('[EMAIL] Berhasil', ['to' => $to]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[EMAIL] Exception: '.$e->getMessage());

            return false;
        }
    }

    // ============================================================
    // Helpers
    // ============================================================

    protected function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Email auto-generate dari sistem (warga0001234@warga.test) — jangan kirim email ke ini. */
    protected function isFakeEmail(string $email): bool
    {
        return str_ends_with($email, '@warga.test') || str_ends_with($email, '@example.test');
    }

    /**
     * Normalisasi nomor: 08xxx → 628xxx, +628xxx → 628xxx.
     * Fonnte/Wablas/Cloud semua minta format internasional tanpa +.
     */
    protected function normalizePhoneFonnte(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            return '628'.substr($phone, 2);
        }
        if (str_starts_with($phone, '+62')) {
            return substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62'.$phone;
        }

        return $phone;
    }
}
