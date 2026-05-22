<?php

namespace App\Services;

use App\Models\Complaint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter integrasi SP4N Lapor.go.id (Sistem Pengelolaan Pengaduan Pelayanan
 * Publik Nasional milik Kemenpan RB).
 *
 * Driver:
 * - mock (default): generate aduan dummy untuk demo.
 * - http: panggil API SPAN4 (butuh kredensial LAPOR_TOKEN + base_url).
 *
 * Aduan baru disinkronkan ke table `complaints` lokal dengan channel='lapor'.
 */
class LaporGoIdService
{
    protected string $driver;
    protected ?string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->driver = config('services.lapor.driver', 'mock');
        $this->baseUrl = config('services.lapor.base_url');
        $this->token = config('services.lapor.token');
    }

    /**
     * Tarik aduan baru dari Lapor.go.id sejak {sinceMinutes} menit lalu.
     * Mengembalikan jumlah aduan baru yang berhasil disinkronkan.
     */
    public function pollNew(int $sinceMinutes = 60): int
    {
        $items = match ($this->driver) {
            'http' => $this->fetchHttp($sinceMinutes),
            default => $this->fetchMock($sinceMinutes),
        };

        $synced = 0;
        foreach ($items as $item) {
            $exists = Complaint::where('channel', 'lapor')
                ->where('reporter_contact', $item['reference_id'] ?? null)
                ->exists();
            if ($exists) continue;

            Complaint::create([
                'code' => Complaint::generateCode(),
                'channel' => 'lapor',
                'reporter_name' => $item['reporter_name'] ?? null,
                'reporter_contact' => $item['reference_id'] ?? null,
                'is_anonymous' => $item['anonymous'] ?? false,
                'subject' => $item['subject'] ?? '(no subject)',
                'content' => $item['content'] ?? '',
                'status' => 'open',
            ]);
            $synced++;
        }

        return $synced;
    }

    /** @return array<int, array{reference_id:string, subject:string, content:string, reporter_name?:string, anonymous?:bool}> */
    protected function fetchMock(int $sinceMinutes): array
    {
        // Generate 0–2 aduan dummy sesuai jam (deterministik agar demo stabil)
        $hour = (int) now()->format('G');
        $count = ($hour % 3); // 0, 1, atau 2

        $samples = [
            ['subject' => 'Bantuan PKH belum cair', 'content' => 'Saya sudah terdaftar PKH tapi sampai sekarang belum menerima bantuan tahap II. Mohon ditindaklanjuti.', 'reporter_name' => 'Sutarno', 'anonymous' => false],
            ['subject' => 'Data DTSEN tidak akurat', 'content' => 'NIK saya muncul di desil 7 padahal kondisi ekonomi keluarga sulit. Bagaimana cara memperbaikinya?', 'reporter_name' => null, 'anonymous' => true],
            ['subject' => 'Disabilitas terlantar di pasar', 'content' => 'Ada saudara disabilitas terlantar di kawasan pasar Pringsewu. Mohon segera ditangani.', 'reporter_name' => 'Anggota masyarakat', 'anonymous' => true],
        ];

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = array_merge($samples[$i], [
                'reference_id' => 'LAPOR-MOCK-'.now()->format('YmdH').'-'.($i + 1),
            ]);
        }
        return $items;
    }

    protected function fetchHttp(int $sinceMinutes): array
    {
        if (! $this->baseUrl || ! $this->token) {
            Log::warning('[LAPOR] base_url/token belum dikonfigurasi, fallback ke mock.');
            return $this->fetchMock($sinceMinutes);
        }

        try {
            $res = Http::withToken($this->token)
                ->timeout(15)
                ->get(rtrim($this->baseUrl, '/').'/sp4n/aduan', [
                    'instansi_id' => config('services.lapor.instansi_id'),
                    'since' => now()->subMinutes($sinceMinutes)->toIso8601String(),
                ]);

            if (! $res->successful()) {
                Log::warning('[LAPOR] HTTP '.$res->status());
                return [];
            }

            return collect($res->json('data', []))->map(function ($r) {
                return [
                    'reference_id' => $r['nomor_aduan'] ?? null,
                    'subject' => $r['judul'] ?? '(tanpa judul)',
                    'content' => $r['isi'] ?? '',
                    'reporter_name' => $r['nama'] ?? null,
                    'anonymous' => $r['anonim'] ?? false,
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::error('[LAPOR] '.$e->getMessage());
            return [];
        }
    }
}
