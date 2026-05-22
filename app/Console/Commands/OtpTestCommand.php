<?php

namespace App\Console\Commands;

use App\Services\NotificationGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Test gateway OTP secara langsung. Pakai untuk verifikasi setup
 * Fonnte / Wablas / Cloud API berfungsi sebelum dipakai user.
 *
 * Contoh:
 *   php artisan otp:test 081234567890
 *   php artisan otp:test budi@gmail.com
 */
class OtpTestCommand extends Command
{
    protected $signature = 'otp:test {target : Nomor HP (08xxx) atau email tujuan}';

    protected $description = 'Kirim OTP test ke nomor/email untuk verifikasi gateway berfungsi';

    public function handle(NotificationGateway $gateway): int
    {
        $target = $this->argument('target');
        $driver = config('services.notifications.driver');

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  OTP Gateway Test — SIMPEL DINSOS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Driver aktif',      $driver],
            ['Target',            $target],
            ['Fonnte token',      config('services.notifications.fonnte_token') ? '✓ terpasang' : '✗ kosong'],
            ['Wablas token',      config('services.notifications.wablas_token') ? '✓ terpasang' : '✗ kosong'],
            ['WhatsApp Cloud',    config('services.notifications.whatsapp_token') ? '✓ terpasang' : '✗ kosong'],
            ['Mail mailer',       config('mail.default')],
        ]);
        $this->newLine();

        // Validasi
        if ($driver === 'log') {
            $this->warn('⚠  Driver = "log" — OTP akan DICETAK ke file, BUKAN dikirim ke WhatsApp.');
            $this->warn('   File: storage/app/private/outbox/'.now()->format('Y-m-d').'.log');
            $this->newLine();
            $this->line('Untuk kirim ke WhatsApp beneran:');
            $this->line('  1. Daftar di https://fonnte.com → scan QR → copy token');
            $this->line('  2. Tambahkan ke .env:');
            $this->line('     NOTIFICATION_DRIVER=fonnte');
            $this->line('     FONNTE_TOKEN=xxxxxxxxxxxxxxx');
            $this->line('  3. Run: php artisan config:clear');
            $this->newLine();
            if (! $this->confirm('Tetap lanjut test ke mode log?', false)) {
                return self::FAILURE;
            }
        }

        if ($driver === 'fonnte' && ! config('services.notifications.fonnte_token')) {
            $this->error('✗ FONNTE_TOKEN belum di-set di .env!');
            return self::FAILURE;
        }

        // Kirim test OTP
        $code = '123456'; // kode test (bukan random)
        $this->line("→ Mengirim kode test <fg=yellow>{$code}</> ke <fg=cyan>{$target}</>…");
        $this->newLine();

        $t0 = microtime(true);
        try {
            $gateway->sendOtp($target, $code);
            $ms = round((microtime(true) - $t0) * 1000, 1);
            $this->info("✓ Gateway memproses dalam {$ms} ms");
            $this->newLine();

            if ($driver === 'log') {
                $this->line('  📄 Cek file: storage/app/private/outbox/'.now()->format('Y-m-d').'.log');
            } elseif ($driver === 'fonnte' || $driver === 'wablas' || $driver === 'cloud') {
                $this->line('  📱 Cek WhatsApp Anda di '.$target);
                $this->line('  📝 Detail log: storage/logs/laravel.log (cari ['.strtoupper($driver).'])');
                $this->newLine();
                $this->line('  Kalau tidak masuk:');
                $this->line('   • Pastikan device di Fonnte/Wablas masih CONNECTED (cek dashboard)');
                $this->line('   • Pastikan nomor tujuan punya WhatsApp aktif');
                $this->line('   • Cek storage/logs/laravel.log untuk error detail');
            } elseif ($driver === 'email') {
                $this->line('  ✉  Cek inbox + folder spam di '.$target);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Error: '.$e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
