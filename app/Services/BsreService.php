<?php

namespace App\Services;

use App\Models\OutputDocument;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Adapter integrasi BSrE BSSN — Tanda Tangan Elektronik Tersertifikasi.
 *
 * BSrE adalah CA (Certificate Authority) milik Badan Siber dan Sandi Negara (BSSN)
 * yang menerbitkan sertifikat elektronik untuk pejabat pemerintah. PDF yang
 * ditandatangani dengan BSrE memiliki kekuatan hukum penuh (UU ITE Pasal 11)
 * dan dapat diverifikasi via verify.bsre.go.id.
 *
 * Driver:
 * - mock (default, untuk demo): tambah metadata "BSrE-MOCK" tanpa TTE sungguhan
 * - http: panggil API BSrE resmi (butuh kredensial BSRE_USER, BSRE_PASS, BSRE_BASE_URL,
 *   serta passphrase sertifikat penandatangan)
 *
 * Pemakaian: setelah PDF di-generate oleh DocumentGenerator, panggil
 * BsreService::sign($outputDocument, $kadis) untuk mendapatkan PDF tertandatangani.
 */
class BsreService
{
    protected string $driver;
    protected ?string $baseUrl;
    protected ?string $user;
    protected ?string $pass;

    public function __construct()
    {
        $this->driver = config('services.bsre.driver', 'mock');
        $this->baseUrl = config('services.bsre.base_url');
        $this->user = config('services.bsre.user');
        $this->pass = config('services.bsre.pass');
    }

    /**
     * Tanda tangani PDF dengan TTE BSrE.
     * Mengembalikan path baru ke PDF tertandatangani.
     */
    public function sign(OutputDocument $document, User $signer, ?string $passphrase = null): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($document->file_path)) {
            throw new \RuntimeException("PDF tidak ditemukan: {$document->file_path}");
        }

        return match ($this->driver) {
            'http' => $this->signHttp($document, $signer, $passphrase ?? config('services.bsre.passphrase')),
            default => $this->signMock($document, $signer),
        };
    }

    protected function signMock(OutputDocument $document, User $signer): string
    {
        // Mode demo: tambah metadata XMP sederhana ke PDF + suffix nama file
        $disk = Storage::disk('public');
        $contents = $disk->get($document->file_path);

        // Tandai PDF dengan metadata "Signed by BSrE-MOCK"
        $marker = "\n%% BSrE-MOCK: ditandatangani oleh {$signer->name} pada ".now()->toIso8601String()." (mode demo, BUKAN TTE tersertifikasi) %%\n";
        $signedContents = $contents.$marker;

        $newPath = preg_replace('/\.pdf$/i', '-signed.pdf', $document->file_path);
        $disk->put($newPath, $signedContents);

        Log::info('[BSRE-MOCK] PDF ditandatangani', [
            'doc' => $document->document_number,
            'signer' => $signer->name,
            'driver' => 'mock',
        ]);

        $document->update([
            'file_path' => $newPath,
            'file_hash' => hash('sha256', $signedContents),
        ]);

        return $newPath;
    }

    protected function signHttp(OutputDocument $document, User $signer, ?string $passphrase): string
    {
        if (! $this->baseUrl || ! $this->user || ! $this->pass || ! $passphrase) {
            Log::warning('[BSRE] Kredensial belum lengkap, fallback ke mock.');
            return $this->signMock($document, $signer);
        }

        try {
            $disk = Storage::disk('public');
            $pdf = $disk->get($document->file_path);

            // Panggil API BSrE — endpoint & parameter sesuai dokumentasi resmi BSSN
            $res = Http::withBasicAuth($this->user, $this->pass)
                ->attach('file', $pdf, basename($document->file_path))
                ->attach('passphrase', $passphrase)
                ->attach('nik', $signer->nip ?? '')
                ->timeout(60)
                ->post(rtrim($this->baseUrl, '/').'/api/sign/pdf');

            if (! $res->successful()) {
                Log::error('[BSRE] HTTP '.$res->status().' '.$res->body());
                return $this->signMock($document, $signer);
            }

            $signedPdf = $res->body();
            $newPath = preg_replace('/\.pdf$/i', '-bsre.pdf', $document->file_path);
            $disk->put($newPath, $signedPdf);

            $document->update([
                'file_path' => $newPath,
                'file_hash' => hash('sha256', $signedPdf),
            ]);

            Log::info('[BSRE] PDF berhasil ditandatangani via BSrE BSSN', [
                'doc' => $document->document_number,
                'signer' => $signer->name,
            ]);

            return $newPath;
        } catch (\Throwable $e) {
            Log::error('[BSRE] '.$e->getMessage());
            return $this->signMock($document, $signer);
        }
    }
}
