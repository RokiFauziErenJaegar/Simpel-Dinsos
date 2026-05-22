<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Complaint;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mesin intent + state machine sederhana untuk WhatsApp Bot inbound.
 *
 * Webhook dari Fonnte / Wablas / dll memanggil handle($phone, $text).
 * Bot mengingat state percakapan di table cache (Laravel cache).
 */
class WhatsAppBot
{
    protected NotificationGateway $gateway;

    public function __construct(NotificationGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function handle(string $from, string $message): string
    {
        $from = $this->normalizePhone($from);
        $text = trim($message);
        $key = "wabot:state:{$from}";
        $state = cache()->get($key, ['step' => 'menu', 'data' => []]);

        // Reset
        if (in_array(strtolower($text), ['menu', 'kembali', 'batal', 'reset', '0'])) {
            cache()->forget($key);
            return $this->reply($from, $this->menuPesan());
        }

        $reply = match ($state['step']) {
            'menu' => $this->handleMenu($from, $text, $state, $key),
            'cek_status_input' => $this->handleCekStatus($text, $key),
            'aduan_subject' => $this->handleAduanSubject($text, $state, $key),
            'aduan_isi' => $this->handleAduanIsi($from, $text, $state, $key),
            'layanan_pilih' => $this->handleLayananPilih($text),
            default => $this->menuPesan(),
        };

        return $this->reply($from, $reply);
    }

    protected function handleMenu(string $from, string $text, array $state, string $key): string
    {
        return match ($text) {
            '1' => (function () use ($key) {
                cache()->put($key, ['step' => 'cek_status_input', 'data' => []], 600);
                return "📋 *Cek Status Pengajuan*\n\nKirim kode pengajuan Anda (contoh: SURAT-2026-0001)\n\nKetik *0* untuk kembali ke menu.";
            })(),
            '2' => (function () use ($key) {
                cache()->put($key, ['step' => 'aduan_subject', 'data' => []], 600);
                return "📢 *Pengaduan Masyarakat*\n\nKetik subjek pengaduan Anda (1 baris singkat).\n\nKetik *0* untuk batal.";
            })(),
            '3' => (function () use ($key) {
                cache()->put($key, ['step' => 'layanan_pilih', 'data' => []], 600);
                $list = ServiceType::active()->orderBy('order_no')->take(16)->get();
                $body = "📋 *Daftar Layanan*\n\n";
                foreach ($list as $i => $s) {
                    $body .= sprintf("%d. %s (%s)\n", $i + 1, Str::limit($s->name, 50), $s->sla_display);
                }
                $body .= "\nBalas angka untuk info detail. *0* untuk menu.";
                return $body;
            })(),
            '4' => "📞 *Kontak Dinsos Pringsewu*\n\n"
                . "• WA / Telp: 0822-6986-7911\n"
                . "• Email: pringsewudinsos@gmail.com\n"
                . "• Alamat: Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemda Pringsewu\n"
                . "• Jam: Senin–Jumat 08.00–15.30\n\n"
                . "Ketik *0* untuk menu utama.",
            default => "Mohon balas dengan angka 1–4.\n\n" . $this->menuPesan(),
        };
    }

    protected function handleCekStatus(string $code, string $key): string
    {
        cache()->forget($key);
        $code = strtoupper(trim($code));
        $app = Application::with(['serviceType', 'logs.user', 'outputDocument'])->where('code', $code)->first();
        if (! $app) {
            return "❌ Kode *{$code}* tidak ditemukan.\nPastikan format benar (huruf besar, dengan tanda hubung).\n\nKetik *0* untuk menu.";
        }

        $status = $app->status?->label() ?? '—';
        $body = "📋 *{$app->code}*\n";
        $body .= "Layanan: {$app->serviceType->name}\n";
        $body .= "Penerima: {$app->beneficiary_name}\n";
        $body .= "Status: *{$status}*\n";
        if ($app->status?->value === 'completed') {
            $body .= "Selesai: {$app->completed_at?->translatedFormat('d M Y H:i')}\n";
            if ($app->outputDocument) {
                $body .= "\n📄 Unduh surat:\n" . route('document.verify', ['token' => $app->outputDocument->verification_token]);
            }
        } elseif ($app->sla_due_at) {
            $body .= "Estimasi selesai: {$app->sla_due_at->translatedFormat('d M Y H:i')}\n";
        }
        $body .= "\nKetik *0* untuk menu utama.";
        return $body;
    }

    protected function handleAduanSubject(string $text, array $state, string $key): string
    {
        if (mb_strlen($text) < 5) {
            return "Subjek terlalu pendek. Mohon ketik subjek minimal 5 karakter.";
        }
        $state['data']['subject'] = mb_substr($text, 0, 200);
        $state['step'] = 'aduan_isi';
        cache()->put($key, $state, 600);
        return "✍ Sekarang kirim isi pengaduan Anda secara lengkap dalam 1 pesan.\n\nKetik *0* untuk batal.";
    }

    protected function handleAduanIsi(string $from, string $text, array $state, string $key): string
    {
        if (mb_strlen($text) < 10) {
            return "Isi pengaduan terlalu pendek. Mohon ketik lebih detail (minimal 10 karakter).";
        }
        $complaint = Complaint::create([
            'code' => Complaint::generateCode(),
            'channel' => 'whatsapp',
            'reporter_contact' => $from,
            'is_anonymous' => false,
            'subject' => $state['data']['subject'] ?? '(via WA bot)',
            'content' => $text,
            'status' => 'open',
        ]);

        cache()->forget($key);
        return "✅ *Aduan terkirim*\n\nKode: *{$complaint->code}*\n\nTim akan menindaklanjuti dan menghubungi Anda kembali.\n\nKetik *0* untuk menu utama.";
    }

    protected function handleLayananPilih(string $text): string
    {
        $idx = (int) $text;
        if ($idx < 1 || $idx > 16) {
            return "Nomor tidak valid. Balas 1–16, atau *0* untuk menu.";
        }
        $service = ServiceType::active()->orderBy('order_no')->skip($idx - 1)->first();
        if (! $service) {
            return "Layanan tidak ditemukan. Ketik *0* untuk menu.";
        }

        $body = "📋 *{$service->name}* ({$service->code})\n\n";
        $body .= $service->description . "\n\n";
        $body .= "⏱ Estimasi: {$service->sla_display}\n";
        $body .= "💰 Biaya: Gratis\n\n";
        $body .= "*Persyaratan:*\n";
        foreach ($service->requirements as $r) {
            $body .= "• " . Str::limit($r, 80) . "\n";
        }
        $body .= "\n📲 Ajukan online:\n" . route('layanan.show', $service->slug);
        $body .= "\n\nKetik *0* untuk menu utama.";
        return $body;
    }

    protected function menuPesan(): string
    {
        return "👋 *Selamat datang di Dinsos Pringsewu!*\n\n"
            . "Saya bisa bantu Anda. Balas dengan angka:\n\n"
            . "1️⃣  Cek status pengajuan\n"
            . "2️⃣  Sampaikan pengaduan\n"
            . "3️⃣  Daftar layanan\n"
            . "4️⃣  Kontak & jam kerja\n\n"
            . "_Ketik *0* / *menu* kapan saja untuk kembali ke menu ini._";
    }

    protected function reply(string $to, string $message): string
    {
        // Outbound dispatch via NotificationGateway (terlihat di outbox)
        try {
            (function () use ($to, $message) {
                $r = new \ReflectionClass(NotificationGateway::class);
                $m = $r->getMethod('dispatch');
                $m->setAccessible(true);
                $m->invoke($this->gateway, 'whatsapp', $to, $message);
            })();
        } catch (\Throwable $e) {
            Log::warning('WA bot reply gagal: '.$e->getMessage());
        }
        return $message;
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) return '628'.substr($phone, 2);
        if (str_starts_with($phone, '+62')) return substr($phone, 1);
        return $phone;
    }
}
