<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\SendOtpJob;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationGateway;
use App\Support\PhoneNumber;
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

        // Selalu normalisasi ulang — jangan percaya bentuk 'contact' apa adanya dari form.
        $phone = PhoneNumber::normalize($data['contact']);

        // Rate-limit percobaan verifikasi per nomor (anti brute-force OTP 6-digit).
        $key = 'otp-verify:'.$phone;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil($seconds / 60).' menit.',
            ]);
        }

        // Ambil OTP TERBARU untuk nomor ini (tanpa filter kode) agar attempts terhitung
        // walau kode salah → guard attempts<5 benar-benar berfungsi.
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->isValid() || ! hash_equals($otp->code, $data['code'])) {
            RateLimiter::hit($key, 900);
            if ($otp && ! $otp->used_at) {
                $otp->increment('attempts');
            }
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ]);
        }

        $otp->update(['used_at' => now()]);
        RateLimiter::clear($key);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Warga '.substr($phone, -4),
                'phone' => $phone,
                'email' => 'warga'.substr($phone, -8).'@warga.test',
                'password' => Hash::make(str()->random(32), ['rounds' => 4]),
                'role' => UserRole::Warga->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate(); // cegah session fixation
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
        return PhoneNumber::normalize($phone);
    }

    protected function maskPhone(string $phone): string
    {
        return PhoneNumber::mask($phone);
    }
}
