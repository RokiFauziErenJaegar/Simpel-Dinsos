<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\DataAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve berkas sensitif (KTP/KK/foto PPKS) dari disk 'secure'
 * dengan otorisasi + audit log.
 */
class SecureFileController extends Controller
{
    public function show(Request $request, int $docId): StreamedResponse
    {
        $doc = ApplicationDocument::with('application.applicant')->findOrFail($docId);
        $user = $request->user();

        // Otorisasi: pemilik pengajuan, internal Dinsos, atau Operator Pekon dari pekon yang sama
        $isOwner = $user && $user->id === $doc->application->applicant_user_id;
        $isInternal = $user && in_array($user->role?->value, ['admin', 'kadis', 'sekretaris', 'kabid', 'kasi', 'petugas']);
        $isPekonOperator = $user && $user->role?->value === 'operator_pekon'
            && $user->pekon_id === $doc->application->applicant?->pekon_id;

        if (! ($isOwner || $isInternal || $isPekonOperator)) {
            abort(403, 'Tidak ada izin akses berkas ini.');
        }

        // Audit log akses berkas
        DataAccessLog::record(
            action: 'view',
            subject: $doc,
            ownerNik: $doc->application->beneficiary_nik,
            reason: 'Lihat berkas pengajuan '.$doc->application->code,
        );

        $disk = Storage::disk('secure');
        if (! $disk->exists($doc->file_path)) {
            abort(404, 'Berkas tidak ditemukan di storage.');
        }

        // Sajikan inline (preview di browser), bukan paksa download.
        // PDF & gambar akan ditampilkan langsung di tab baru; tipe lain
        // yang tak bisa dirender browser tetap diunduh otomatis.
        return $disk->response(
            $doc->file_path,
            $doc->original_name,
            [
                'Content-Type' => $doc->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.addslashes($doc->original_name).'"',
            ]
        );
    }
}
