<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\SendOtpJob;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class WargaAuthController extends Controller
{
    public function showLogin()
    {
        return view('public.auth.login');
    }

    /**
     * Kirim OTP via WhatsApp.
     * Rate-limit: max 5 percobaan per 15 menit per nomor (anti-spam OTP).
     */
    public function sendOtp(Request $request, NotificationGateway $gateway)
    {
        $data = $request->validate([
            'contact' => 'required|string|max:20',
        ]);

        $contact = trim($data['contact']);

        if (! preg_match('/^[\d+\s\-()]{8,20}$/', $contact)) {
            throw ValidationException::withMessages([
                'contact' => 'Masukkan nomor WhatsApp yang valid (contoh: 08xxxxxxxxxx).',
            ]);
        }

        $normalized = $this->normalizePhone($contact);

        // Rate limit anti-spam OTP
        $key = 'otp-send:'.$normalized;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'contact' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil($seconds / 60).' menit.',
            ]);
        }
        RateLimiter::hit($key, 900); // window 15 menit

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $normalized, // kolom 'phone' dipakai untuk HP & email
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Push ke queue worker. Sebelumnya pakai afterResponse() tapi di Railway
        // (PHP-FPM + nginx) fastcgi_finish_request tidak reliable, callback kadang
        // tidak fire. True queue lebih robust untuk production.
        SendOtpJob::dispatch($normalized, $code);

        $maskedTarget = $this->maskPhone($normalized);

        $note = config('services.notifications.driver') === 'log'
            ? 'Mode demo aktif — cek file storage/app/private/outbox/'.now()->format('Y-m-d').'.log untuk lihat kode OTP.'
            : 'Kode OTP telah dikirim ke '.$maskedTarget.'. Berlaku 5 menit.';

        return redirect()
            ->route('warga.otp.verify', ['contact' => $normalized])
            ->with('success', $note);
    }

    public function showVerify(string $contact)
    {
        return view('public.auth.verify', [
            'contact' => $contact,
            'masked' => $this->maskPhone($contact),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'contact' => 'required|string|max:20',
            'code' => 'required|string|size:6',
        ]);

        $otp = OtpCode::where('phone', $data['contact'])
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->isValid()) {
            if ($otp) $otp->increment('attempts');
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ]);
        }

        $otp->update(['used_at' => now()]);

        $user = User::where('phone', $data['contact'])->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Warga '.substr($data['contact'], -4),
                'phone' => $data['contact'],
                'email' => 'warga'.substr($data['contact'], -8).'@warga.test',
                'password' => Hash::make(str()->random(32), ['rounds' => 4]),
                'role' => UserRole::Warga->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $user->update(['last_login_at' => now()]);

        // Hormati url.intended HANYA bila menunjuk ke area warga.
        // Jangan pernah lempar warga ke /admin (panel Filament) — di sana
        // mereka akan kena 403 karena bukan akun internal.
        $intended = $request->session()->pull('url.intended');
        $intendedPath = $intended ? parse_url($intended, PHP_URL_PATH) : null;
        $isAdminPath = $intendedPath && str_starts_with($intendedPath, '/admin');

        return ($intended && ! $isAdminPath)
            ? redirect()->to($intended)
            : redirect()->route('warga.dashboard');
    }

    public function dashboard()
    {
        $user = Auth::user();
        $applications = $user->applications()->with('serviceType', 'queueTicket')->latest()->take(10)->get();
        return view('public.warga.dashboard', compact('user', 'applications'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) return '628'.substr($phone, 2);
        if (str_starts_with($phone, '+62')) return substr($phone, 1);
        if (str_starts_with($phone, '8')) return '62'.$phone;
        return $phone;
    }

    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 6) return $phone;
        return substr($phone, 0, 4).str_repeat('*', strlen($phone) - 7).substr($phone, -3);
    }
}
