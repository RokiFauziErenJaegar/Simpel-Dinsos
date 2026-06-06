<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\DataAccessLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hak Subjek Data — UU PDP 27/2022 Pasal 4–15:
 *  - Hak akses & portabilitas (export JSON)
 *  - Hak menghapus (right to be forgotten)
 *  - Hak menarik persetujuan
 */
class WargaDataRightsController extends Controller
{
    public function showDataRights()
    {
        $user = Auth::user();

        return view('public.warga.data-rights', ['user' => $user]);
    }

    public function exportJson(Request $request): StreamedResponse
    {
        $user = $request->user();

        $payload = [
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'data_owner' => $user->name,
                'compliance' => 'UU PDP 27/2022 Pasal 13 — Hak Portabilitas Data',
                'note' => 'Berkas ini berisi seluruh data pribadi Anda di SIMPEL DINSOS. Simpan dengan aman.',
            ],
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'nik' => $user->nik, // sudah auto-decrypt
                'phone' => $user->phone,
                'address' => $user->address,
                'kecamatan' => $user->kecamatan?->name,
                'pekon' => $user->pekon?->name,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'ppks_profile' => $user->ppksProfile,
            'applications' => $user->applications()->with(['serviceType', 'documents', 'logs', 'outputDocument'])->get(),
        ];

        // Catat akses portabilitas
        DataAccessLog::record(
            action: 'export',
            subject: $user,
            ownerNik: $user->nik,
            reason: 'Hak portabilitas data oleh subjek (UU PDP Pasal 13)',
        );

        $filename = 'data-saya-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function requestDeletion(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'confirm' => 'required|in:HAPUS DATA SAYA',
            'reason' => 'nullable|string|max:500',
        ]);

        // Soft-delete: data masih ada 30 hari sebelum di-force-delete oleh pdp:scrub
        $user->applications()->each(function (Application $app) {
            $app->delete();
        });
        $user->ppksProfile?->delete();

        DataAccessLog::record(
            action: 'self-delete',
            subject: $user,
            ownerNik: $user->nik,
            reason: 'Penarikan persetujuan oleh subjek: '.($data['reason'] ?? '—'),
        );

        $userId = $user->id;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Soft-delete user
        User::where('id', $userId)->delete();

        return redirect()->route('home')->with('success',
            'Permintaan penghapusan data berhasil. Data Anda akan dihapus permanen dalam 30 hari sesuai SOP retensi. '
            .'Untuk pembatalan, hubungi 0822-6986-7911 sebelum batas waktu.');
    }
}
