<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter integrasi DTSEN (Data Terpadu Sosial Ekonomi Nasional).
 *
 * Driver:
 * - mock (default): generate desil 1-10 deterministik dari NIK.
 * - http: panggil API resmi (butuh DTSEN_TOKEN + DTSEN_BASE_URL).
 */
class DtsenService
{
    protected string $driver;

    protected ?string $baseUrl;

    protected ?string $token;

    public function __construct()
    {
        $this->driver = config('services.dtsen.driver', 'mock');
        $this->baseUrl = config('services.dtsen.base_url');
        $this->token = config('services.dtsen.token');
    }

    /**
     * @return array{found:bool, desil?:int, klaster?:string, no_kk?:string, anggota_keluarga?:int}
     */
    public function lookupNik(string $nik): array
    {
        if (! preg_match('/^\d{16}$/', $nik)) {
            return ['found' => false, 'error' => 'Format NIK tidak valid.'];
        }

        return match ($this->driver) {
            'http' => $this->lookupHttp($nik),
            default => $this->lookupMock($nik),
        };
    }

    protected function lookupMock(string $nik): array
    {
        // ~70% NIK dianggap terdaftar DTSEN (untuk demo)
        $last = (int) substr($nik, -4);
        if ($last % 10 < 3) {
            return ['found' => false, 'error' => 'NIK tidak terdaftar dalam DTSEN.'];
        }

        $desil = (($last % 10) - 2);
        $desil = max(1, min(10, $desil));

        $klaster = match (true) {
            $desil <= 1 => 'Sangat Miskin',
            $desil <= 2 => 'Miskin',
            $desil <= 4 => 'Rentan Miskin',
            $desil <= 6 => 'Menengah Bawah',
            default => 'Menengah/Atas',
        };

        return [
            'found' => true,
            'source' => 'mock',
            'nik' => $nik,
            'desil' => $desil,
            'klaster' => $klaster,
            'no_kk' => substr($nik, 0, 12).'0001',
            'anggota_keluarga' => 3 + ($last % 4),
            'updated_at' => now()->subMonths(($last % 6))->format('Y-m-d'),
        ];
    }

    protected function lookupHttp(string $nik): array
    {
        if (! $this->baseUrl || ! $this->token) {
            Log::warning('[DTSEN] base_url/token belum dikonfigurasi, fallback mock.');

            return $this->lookupMock($nik);
        }
        try {
            $res = Http::withToken($this->token)
                ->timeout(10)
                ->get(rtrim($this->baseUrl, '/').'/dtsen/'.$nik);
            if ($res->successful()) {
                return array_merge(['found' => true, 'source' => 'dtsen'], $res->json());
            }

            return ['found' => false, 'error' => 'NIK tidak terdaftar di DTSEN.'];
        } catch (\Throwable $e) {
            Log::error('[DTSEN] '.$e->getMessage());

            return ['found' => false, 'error' => 'Gangguan koneksi DTSEN. Coba lagi.'];
        }
    }
}
