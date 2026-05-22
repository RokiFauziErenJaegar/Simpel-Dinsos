<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter integrasi Dukcapil — cek NIK terverifikasi.
 *
 * Driver:
 * - mock (default): generate data dummy konsisten berdasarkan NIK.
 * - http: panggil API resmi (butuh kredensial DUKCAPIL_TOKEN + DUKCAPIL_BASE_URL).
 *
 * Antarmuka publik tetap sama agar swap driver tidak butuh ubah pemanggil.
 */
class DukcapilService
{
    protected string $driver;
    protected ?string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->driver = config('services.dukcapil.driver', 'mock');
        $this->baseUrl = config('services.dukcapil.base_url');
        $this->token = config('services.dukcapil.token');
    }

    /**
     * @return array{found:bool, nama?:string, tempat_lahir?:string, tanggal_lahir?:string, jenis_kelamin?:string, alamat?:string}
     */
    public function lookupNik(string $nik): array
    {
        if (! preg_match('/^\d{16}$/', $nik)) {
            return ['found' => false, 'error' => 'Format NIK tidak valid (harus 16 digit angka).'];
        }

        return match ($this->driver) {
            'http' => $this->lookupHttp($nik),
            default => $this->lookupMock($nik),
        };
    }

    protected function lookupMock(string $nik): array
    {
        // Generate data deterministik berdasarkan NIK
        $kodeKab = substr($nik, 0, 6);
        if ($kodeKab !== '187103') {
            return ['found' => false, 'error' => 'NIK terdaftar bukan di Kabupaten Pringsewu (kode wilayah tidak cocok).'];
        }

        $tglLahirRaw = substr($nik, 6, 6); // DDMMYY
        $day = (int) substr($tglLahirRaw, 0, 2);
        $isFemale = $day > 40;
        if ($isFemale) $day -= 40;
        $month = (int) substr($tglLahirRaw, 2, 2);
        $year = (int) substr($tglLahirRaw, 4, 2);
        $year = $year < 30 ? 2000 + $year : 1900 + $year;

        $names = ['Budi Santoso', 'Siti Maesaroh', 'Eko Pranoto', 'Rina Pratiwi', 'Suparmin', 'Yanti Wulandari', 'Ahmad Fauzi', 'Dewi Lestari'];
        $idx = intval(substr($nik, -4)) % count($names);

        return [
            'found' => true,
            'source' => 'mock',
            'nik' => $nik,
            'nama' => $names[$idx],
            'tempat_lahir' => 'Pringsewu',
            'tanggal_lahir' => sprintf('%04d-%02d-%02d', $year, $month, $day),
            'jenis_kelamin' => $isFemale ? 'Perempuan' : 'Laki-laki',
            'alamat' => 'Pekon Pringsewu Utara, Kab. Pringsewu',
            'agama' => 'Islam',
        ];
    }

    protected function lookupHttp(string $nik): array
    {
        if (! $this->baseUrl || ! $this->token) {
            Log::warning('[DUKCAPIL] base_url/token belum dikonfigurasi, fallback mock.');
            return $this->lookupMock($nik);
        }

        try {
            $res = Http::withToken($this->token)
                ->timeout(10)
                ->get(rtrim($this->baseUrl, '/').'/identity/'.$nik);

            if ($res->successful()) {
                return array_merge(['found' => true, 'source' => 'dukcapil'], $res->json());
            }
            return ['found' => false, 'error' => 'NIK tidak ditemukan di pangkalan data Dukcapil.'];
        } catch (\Throwable $e) {
            Log::error('[DUKCAPIL] '.$e->getMessage());
            return ['found' => false, 'error' => 'Gangguan koneksi ke Dukcapil. Coba lagi nanti.'];
        }
    }
}
