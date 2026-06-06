<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    public function show(Request $request, TwoFactorService $svc)
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            $secret = $svc->generateSecret();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        $otpUrl = $svc->qrCodeUrl($user, $user->two_factor_secret);
        $qrSvg = QrCode::format('svg')->size(220)->margin(1)->generate($otpUrl);

        return view('public.warga.two-factor', [
            'user' => $user,
            'qrSvg' => $qrSvg,
            'secret' => $user->two_factor_secret,
            // Recovery codes hanya ditampilkan SEKALI (plaintext via flash) saat
            // baru di-generate; di DB tersimpan hashed.
            'recoveryCodes' => session('recovery_codes_plain', []),
        ]);
    }

    public function confirm(Request $request, TwoFactorService $svc)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $svc->verify($user->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Kode TOTP tidak valid. Coba lagi dari aplikasi authenticator Anda.']);
        }

        $codes = $svc->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            // Simpan HASH recovery code, bukan plaintext.
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes),
        ])->save();

        return redirect()->route('two-factor.show')
            ->with('success', '2FA berhasil diaktifkan! Simpan recovery codes di bawah ini — hanya ditampilkan sekali.')
            ->with('recovery_codes_plain', $codes);
    }

    public function disable(Request $request)
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('two-factor.show')->with('success', '2FA dinonaktifkan.');
    }

    public function challenge()
    {
        return view('public.warga.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TwoFactorService $svc)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $userId = $request->session()->get('2fa.user_id');
        if (! $userId) {
            return redirect()->route('warga.login');
        }

        // Throttle anti brute-force TOTP/recovery (5 percobaan / 15 menit).
        $key = '2fa-verify:'.$userId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.',
            ]);
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('warga.login');
        }

        $code = trim($data['code']);

        // Cek recovery code. Recovery code baru tersimpan ter-hash (Hash::check);
        // namun ada data LAMA yang tersimpan plaintext sebelum P1-9 → Hash::check
        // akan melempar RuntimeException pada hash non-bcrypt. Tangani keduanya.
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        foreach ($recoveryCodes as $idx => $stored) {
            $matches = false;
            try {
                $matches = Hash::isHashed($stored) && Hash::check($code, $stored);
            } catch (\Throwable $e) {
                $matches = false;
            }
            // Fallback recovery code lama (plaintext), perbandingan constant-time.
            if (! $matches && ! Hash::isHashed($stored)) {
                $matches = hash_equals((string) $stored, $code);
            }

            if ($matches) {
                unset($recoveryCodes[$idx]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();
                RateLimiter::clear($key);
                $this->completeLogin($request, $user);

                return redirect()->intended('/admin');
            }
        }

        // Cek TOTP
        if ($svc->verify($user->two_factor_secret, $code)) {
            RateLimiter::clear($key);
            $this->completeLogin($request, $user);

            return redirect()->intended('/admin');
        }

        RateLimiter::hit($key, 900);

        return back()->withErrors(['code' => 'Kode tidak valid.']);
    }

    protected function completeLogin(Request $request, $user): void
    {
        Auth::login($user, true);
        $request->session()->regenerate(); // cegah session fixation
        $request->session()->forget('2fa.user_id');
        $request->session()->put('2fa.verified', true);
        $user->update(['last_login_at' => now()]);
    }
}
