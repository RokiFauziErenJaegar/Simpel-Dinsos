<?php

use App\Http\Controllers\AccountSwitchController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationResubmitController;
use App\Http\Controllers\KieController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\OperatorPekonController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SatisfactionSurveyController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\TvDisplayController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WargaAuthController;
use App\Http\Controllers\WargaDataRightsController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Services\DtsenService;
use App\Services\DukcapilService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// =========================
// Healthcheck untuk Railway / load balancer
// Cek koneksi DB + kembalikan 200 OK. Tidak panggil session / cache stack.
// =========================
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'db' => config('database.default'),
            'app' => config('app.name'),
            'time' => now()->toIso8601String(),
        ], 200);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database unreachable',
        ], 503);
    }
})->withoutMiddleware([
    VerifyCsrfToken::class,
]);

// =========================
// Public site
// =========================
Route::get('/', [PublicController::class, 'home'])->name('home');

Route::prefix('layanan')->name('layanan.')->group(function () {
    Route::get('/', [PublicController::class, 'services'])->name('index');
    Route::get('/{slug}', [PublicController::class, 'serviceShow'])->name('show');
    Route::get('/{slug}/ajukan', [ApplicationController::class, 'create'])->name('ajukan');
    Route::post('/{slug}/ajukan', [ApplicationController::class, 'store'])->name('ajukan.kirim');
});

Route::prefix('pengajuan')->name('pengajuan.')->group(function () {
    Route::get('/sukses/{code}', [ApplicationController::class, 'success'])->name('sukses');
});

Route::get('/cek-status', [PublicController::class, 'checkStatusIndex'])->name('cek-status.index');

Route::get('/pengaduan', [PublicController::class, 'complaintCreate'])->name('pengaduan.create');
Route::post('/pengaduan', [PublicController::class, 'complaintStore'])->name('pengaduan.store');

Route::get('/verify/{token}', [PublicController::class, 'verifyDocument'])->name('document.verify');
// Unduh surat hasil — wajib isi SKM dulu (gerbang ada di controller).
Route::get('/surat/{token}/unduh', [PublicController::class, 'downloadDocument'])->name('document.download');

// =========================
// Warga Auth (OTP WhatsApp)
// =========================
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [WargaAuthController::class, 'showLogin'])->name('warga.login');
    Route::post('/masuk/otp', [WargaAuthController::class, 'sendOtp'])->name('warga.otp.send');
    Route::get('/masuk/verifikasi/{contact}', [WargaAuthController::class, 'showVerify'])
        ->where('contact', '.*') // izinkan @ dan . untuk email
        ->name('warga.otp.verify');
    Route::post('/masuk/verifikasi', [WargaAuthController::class, 'verifyOtp'])->name('warga.otp.verify.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/akun', [WargaAuthController::class, 'dashboard'])->name('warga.dashboard');
    Route::post('/keluar', [WargaAuthController::class, 'logout'])->name('warga.logout');

    // Perbaiki & kirim ulang pengajuan yang dikembalikan
    Route::get('/akun/pengajuan/{code}/perbaiki', [ApplicationResubmitController::class, 'edit'])->name('warga.application.fix');
    Route::post('/akun/pengajuan/{code}/perbaiki', [ApplicationResubmitController::class, 'update'])->name('warga.application.fix.submit');

    // Hak atas data pribadi (UU PDP)
    Route::get('/akun/data-saya', [WargaDataRightsController::class, 'showDataRights'])->name('warga.data.rights');
    Route::get('/akun/data-saya/ekspor', [WargaDataRightsController::class, 'exportJson'])->name('warga.data.export');
    Route::post('/akun/data-saya/hapus', [WargaDataRightsController::class, 'requestDeletion'])->name('warga.data.delete');

    // 2FA
    Route::get('/akun/2fa', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/akun/2fa/aktifkan', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/akun/2fa/nonaktifkan', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
});

// 2FA challenge (di luar group middleware auth karena user belum sepenuhnya login)
Route::get('/2fa/verifikasi', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
Route::post('/2fa/verifikasi', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify-challenge');

// =========================
// Multi-akun petugas (ala Instagram/WhatsApp)
// Tambah akun kedua tanpa mengeluarkan akun aktif, lalu pindah tanpa password.
// Sengaja tanpa gerbang 2FA di sini — lihat AccountSwitchController.
// =========================
Route::middleware('auth')->prefix('akun-petugas')->name('account.')->group(function () {
    Route::get('/tambah', [AccountSwitchController::class, 'create'])->name('add');
    Route::post('/tambah', [AccountSwitchController::class, 'store'])->name('add.store');
    Route::post('/pindah/{userId}', [AccountSwitchController::class, 'switch'])
        ->whereNumber('userId')->name('switch');
    Route::post('/lupakan/{userId}', [AccountSwitchController::class, 'forget'])
        ->whereNumber('userId')->name('forget');
});

// =========================
// Operator Pekon
// =========================
Route::middleware(['auth', 'role:operator_pekon'])->prefix('pekon')->name('pekon.')->group(function () {
    Route::get('/', [OperatorPekonController::class, 'dashboard'])->name('dashboard');
    Route::get('/ajukan', [OperatorPekonController::class, 'createApplication'])->name('ajukan');
    Route::post('/ajukan', [OperatorPekonController::class, 'storeApplication'])->name('ajukan.kirim');
});

// =========================
// Konsultasi Warga (KIE) — pendaftaran mandiri warga
// =========================
Route::get('/kie', [KieController::class, 'create'])->name('kie.create');
Route::post('/kie', [KieController::class, 'store'])->name('kie.kirim');
Route::get('/kie/sukses/{code}', [KieController::class, 'success'])->name('kie.sukses');

// =========================
// Survei Kepuasan Masyarakat
// =========================
Route::get('/skm/{code}', [SatisfactionSurveyController::class, 'create'])->name('skm.create');
Route::post('/skm/{code}', [SatisfactionSurveyController::class, 'store'])->name('skm.store');

// Statistik Kepuasan Masyarakat (publik) — fitur 4
Route::get('/statistik-kepuasan', [SatisfactionSurveyController::class, 'publicStats'])->name('skm.stats');

// =========================
// TV Lobi
// =========================
Route::get('/tv', [TvDisplayController::class, 'display'])->name('tv.display');
Route::get('/tv/live', [TvDisplayController::class, 'liveData'])->name('tv.live');
Route::get('/tv/debug', [TvDisplayController::class, 'debug'])->name('tv.debug');
Route::view('/tv/reset', 'public.tv-reset')->name('tv.reset');

// =========================
// Kiosk Self-Service Lobi
// =========================
Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::post('/kiosk/tiket', [KioskController::class, 'takeTicket'])->name('kiosk.ticket');

// =========================
// AJAX: Lookup NIK (Dukcapil + DTSEN)
// =========================
// Hanya petugas internal & operator pekon (yang memang melayani pengajuan) yang boleh
// lookup NIK. Endpoint AJAX/JSON → kembalikan 401/403 JSON (bukan redirect), + throttle
// agar tidak bisa dipakai enumerasi data Dukcapil/DTSEN.
Route::get('/api/nik/{nik}', function (string $nik) {
    $user = auth()->user();
    if (! $user) {
        return response()->json(['error' => 'Harus login.'], 401);
    }
    if (! in_array($user->role?->value, ['admin', 'kadis', 'sekretaris', 'kabid', 'kasi', 'petugas', 'operator_pekon'])) {
        return response()->json(['error' => 'Tidak ada izin lookup NIK.'], 403);
    }

    return response()->json([
        'dukcapil' => app(DukcapilService::class)->lookupNik($nik),
        'dtsen' => app(DtsenService::class)->lookupNik($nik),
    ]);
})->where('nik', '[0-9]{16}')->middleware('throttle:20,1')->name('api.nik');

// =========================
// WhatsApp Bot Webhook (inbound)
// =========================
Route::post('/webhook/wa', [WhatsAppWebhookController::class, 'inbound'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhook.wa');
Route::post('/webhook/wa/simulate', [WhatsAppWebhookController::class, 'simulate'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhook.wa.simulate');

// Halaman demo simulator WA bot
Route::view('/wa-demo', 'public.wa-demo')->name('wa.demo');

// PWA: halaman offline fallback (dilayani service worker saat offline)
Route::view('/offline', 'public.offline')->name('offline');
Route::get('/pwa-test', [PwaController::class, 'deviceTest'])->name('pwa.test');
Route::post('/pwa/subscribe', [PwaController::class, 'subscribePush'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('pwa.subscribe');
Route::get('/pwa/vapid-key', [PwaController::class, 'vapidPublicKey'])->name('pwa.vapid');
Route::post('/pwa/test-push', [PwaController::class, 'testServerPush'])
    ->middleware('auth')
    ->name('pwa.test-push');

// Berkas sensitif (KTP/KK/foto PPKS) — perlu auth + audit log
Route::middleware('auth')->get('/secure-file/{docId}',
    [SecureFileController::class, 'show'])->name('secure.file');

// Dokumen terbitan (surat hasil layanan yang sudah terbit) — perlu auth + audit log
Route::middleware('auth')->get('/dokumen-terbitan/{docId}',
    [SecureFileController::class, 'showOutput'])->name('output.file');
