<?php

namespace App\Services;

use App\Models\Application;
use App\Models\OutputDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentGeneratorHelpers
{
    public static function fileToBase64(?string $relativePath): ?string
    {
        if (! $relativePath) return null;
        if (! Storage::disk('public')->exists($relativePath)) return null;
        $contents = Storage::disk('public')->get($relativePath);
        $mime = match (true) {
            str_ends_with($relativePath, '.svg') => 'image/svg+xml',
            str_ends_with($relativePath, '.png') => 'image/png',
            str_ends_with($relativePath, '.jpg'), str_ends_with($relativePath, '.jpeg') => 'image/jpeg',
            default => 'image/png',
        };
        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}

class DocumentGenerator
{
    /**
     * Generate PDF surat resmi + QR code untuk pengajuan yang sudah diproses.
     * Mengembalikan instance OutputDocument yang sudah tersimpan.
     */
    public function issue(Application $application, User $signer, ?string $template = null): OutputDocument
    {
        // Pastikan signer punya tanda tangan & stempel (auto-generate kalau belum)
        app(SignatureAssetGenerator::class)->ensureDefaults($signer);

        $token = bin2hex(random_bytes(20));
        $docNumber = $this->nextDocumentNumber($application);

        // QR berisi URL verifikasi publik
        $verifyUrl = route('document.verify', ['token' => $token]);
        $qrSvg = base64_encode(QrCode::format('svg')->size(120)->margin(0)->generate($verifyUrl));

        $view = $template ?? 'documents.surat-generic';
        $pdf = Pdf::loadView($view, [
            'app' => $application->loadMissing(['serviceType', 'applicant', 'documents']),
            'service' => $application->serviceType,
            'signer' => $signer,
            'docNumber' => $docNumber,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $qrSvg,
            'signatureDataUri' => DocumentGeneratorHelpers::fileToBase64($signer->signature_path),
            'stampDataUri' => DocumentGeneratorHelpers::fileToBase64($signer->stamp_path),
        ])->setPaper('a4');

        $filename = sprintf('surat-%s.pdf', strtolower(str_replace('/', '_', $docNumber)));
        $relativePath = "documents/{$application->id}/{$filename}";
        Storage::disk('public')->put($relativePath, $pdf->output());

        $document = OutputDocument::create([
            'application_id' => $application->id,
            'document_number' => $docNumber,
            'file_path' => $relativePath,
            'verification_token' => $token,
            'signed_by_user_id' => $signer->id,
            'signed_at' => now(),
            'file_hash' => hash('sha256', $pdf->output()),
        ]);

        // TTE BSrE opsional — aktifkan via BSRE_ENABLED=true di .env
        if (config('services.bsre.enabled')) {
            try {
                app(BsreService::class)->sign($document, $signer);
                $document->refresh();
            } catch (\Throwable $e) {
                \Log::warning('BSrE sign gagal, lanjutkan tanpa TTE tersertifikasi: '.$e->getMessage());
            }
        }

        return $document;
    }

    protected function nextDocumentNumber(Application $application): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $seq = OutputDocument::whereYear('created_at', $year)->count() + 1;

        // Format: 920/{seq}/D.04/{month}/{year} (mengikuti pola Maklumat 920/460/D.04/X/2023)
        return sprintf('920/%03d/D.04/%s/%s', $seq, $month, $year);
    }
}
