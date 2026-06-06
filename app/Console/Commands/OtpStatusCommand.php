<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Cek status konfigurasi gateway OTP. Validasi:
 * - Driver yang aktif
 * - Token terpasang
 * - Konektivitas ke API gateway (Fonnte / Wablas / WA Cloud)
 *
 * php artisan otp:status
 */
class OtpStatusCommand extends Command
{
    protected $signature = 'otp:status';

    protected $description = 'Cek status konfigurasi gateway OTP & ping ke provider';

    public function handle(): int
    {
        $driver = config('services.notifications.driver');

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  Status Gateway OTP — SIMPEL DINSOS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Driver status
        $statusIcon = match ($driver) {
            'log' => '<fg=yellow>⚠  MODE DEMO</>',
            'fonnte', 'wablas', 'cloud' => '<fg=green>✓ AKTIF</>',
            'email' => '<fg=green>✓ AKTIF (EMAIL)</>',
            default => '<fg=red>✗ TIDAK DIKENAL</>',
        };

        $this->line('Driver aktif: <fg=cyan>'.$driver.'</> '.$statusIcon);
        $this->newLine();

        // Detail per driver
        switch ($driver) {
            case 'log':
                $this->warn('OTP tidak dikirim ke WhatsApp — hanya ditulis ke file.');
                $this->line('File outbox: storage/app/private/outbox/'.now()->format('Y-m-d').'.log');
                $this->newLine();
                $this->showSetupInstructions();
                break;

            case 'fonnte':
                $this->checkFonnte();
                break;

            case 'wablas':
                $this->checkWablas();
                break;

            case 'cloud':
                $this->checkCloud();
                break;

            case 'email':
                $this->checkEmail();
                break;
        }

        // Fallback
        if (config('services.notifications.fallback_email')) {
            $this->newLine();
            $this->info('✓ Fallback email aktif — kalau WA gagal, kirim via email');
        }

        $this->newLine();
        $this->line('Test kirim OTP: <fg=cyan>php artisan otp:test 081234567890</>');
        $this->line('Detail panduan: <fg=cyan>docs/WA_GATEWAY_SETUP.md</>');

        return self::SUCCESS;
    }

    protected function checkFonnte(): void
    {
        $token = config('services.notifications.fonnte_token');

        if (! $token) {
            $this->error('✗ FONNTE_TOKEN belum di-set di .env');
            $this->newLine();
            $this->line('Cara mendapatkan token:');
            $this->line('  1. Daftar di https://fonnte.com');
            $this->line('  2. Dashboard → Device → Add Device → scan QR');
            $this->line('  3. Copy token, paste ke .env');

            return;
        }

        $this->line('Token: <fg=cyan>'.substr($token, 0, 8).'••••</> (tersembunyi)');
        $this->newLine();

        $this->line('Ping ke api.fonnte.com…');
        try {
            $res = Http::withHeaders(['Authorization' => $token])
                ->timeout(8)
                ->asForm()
                ->post('https://api.fonnte.com/validate');

            if ($res->successful()) {
                $data = $res->json();
                $this->info('  ✓ Token valid');
                if (isset($data['device'])) {
                    $this->line('  📱 Device: '.json_encode($data['device']));
                }
                if (isset($data['quota'])) {
                    $this->line('  📊 Quota: '.json_encode($data['quota']));
                }
            } else {
                $this->error('  ✗ HTTP '.$res->status());
                $this->line('  Response: '.$res->body());
            }
        } catch (\Throwable $e) {
            $this->error('  ✗ Koneksi gagal: '.$e->getMessage());
        }
    }

    protected function checkWablas(): void
    {
        $token = config('services.notifications.wablas_token');
        $domain = config('services.notifications.wablas_domain');

        if (! $token) {
            $this->error('✗ WABLAS_TOKEN belum di-set di .env');

            return;
        }
        $this->line('Token: <fg=cyan>'.substr($token, 0, 8).'••••</>');
        $this->line('Domain: <fg=cyan>'.$domain.'</>');
    }

    protected function checkCloud(): void
    {
        $token = config('services.notifications.whatsapp_token');
        $phoneId = config('services.notifications.whatsapp_phone_id');

        if (! $token || ! $phoneId) {
            $this->error('✗ WHATSAPP_TOKEN atau WHATSAPP_PHONE_ID belum di-set');

            return;
        }
        $this->line('Phone ID: <fg=cyan>'.$phoneId.'</>');
        $this->line('Token: <fg=cyan>'.substr($token, 0, 12).'••••</>');

        try {
            $res = Http::withToken($token)
                ->timeout(8)
                ->get("https://graph.facebook.com/v18.0/{$phoneId}");
            if ($res->successful()) {
                $this->info('  ✓ WA Business number valid');
                $data = $res->json();
                if (isset($data['display_phone_number'])) {
                    $this->line('  📱 Nomor: '.$data['display_phone_number']);
                }
            } else {
                $this->error('  ✗ HTTP '.$res->status().' — token/phone-id mungkin invalid');
            }
        } catch (\Throwable $e) {
            $this->error('  ✗ Koneksi gagal: '.$e->getMessage());
        }
    }

    protected function checkEmail(): void
    {
        $this->line('Mail mailer: <fg=cyan>'.config('mail.default').'</>');
        $this->line('SMTP host: <fg=cyan>'.config('mail.mailers.smtp.host').'</>');
        $this->line('SMTP port: <fg=cyan>'.config('mail.mailers.smtp.port').'</>');
        $this->line('From: <fg=cyan>'.config('mail.from.address').'</>');
    }

    protected function showSetupInstructions(): void
    {
        $this->line('<fg=yellow>Pilihan gateway gratis:</>');
        $this->newLine();
        $this->line('  <fg=cyan>1. Fonnte</> (paling cepat, gratis 100 pesan/hari)');
        $this->line('     Daftar: https://fonnte.com');
        $this->line('     .env:');
        $this->line('       NOTIFICATION_DRIVER=fonnte');
        $this->line('       FONNTE_TOKEN=xxxxxxxxxxxxxxx');
        $this->newLine();
        $this->line('  <fg=cyan>2. WhatsApp Cloud API</> (resmi Meta, gratis 1000 conv/bulan)');
        $this->line('     Daftar: https://developers.facebook.com');
        $this->line('     .env:');
        $this->line('       NOTIFICATION_DRIVER=cloud');
        $this->line('       WHATSAPP_TOKEN=EAAxxxxxxxx');
        $this->line('       WHATSAPP_PHONE_ID=12345678');
        $this->newLine();
        $this->line('  <fg=cyan>3. Email SMTP</> (fallback / kalau warga tidak punya WA)');
        $this->line('     .env:');
        $this->line('       NOTIFICATION_DRIVER=email');
        $this->line('       MAIL_MAILER=smtp');
        $this->line('       MAIL_HOST=smtp.gmail.com  (atau provider lain)');
        $this->newLine();
        $this->line('Setelah update .env: <fg=green>php artisan config:clear</>');
    }
}
