<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'recoveryCodes' => $user->two_factor_recovery_codes,
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
            'two_factor_recovery_codes' => $codes,
        ])->save();

        return redirect()->route('two-factor.show')->with('success',
            '2FA berhasil diaktifkan! Simpan recovery codes di bawah ini.');
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

        $user = \App\Models\User::find($userId);
        if (! $user) {
            return redirect()->route('warga.login');
        }

        $code = trim($data['code']);

        // Cek apakah ini recovery code
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        if (in_array($code, $recoveryCodes, true)) {
            // Hapus recovery code yang dipakai
            $remaining = array_values(array_diff($recoveryCodes, [$code]));
            $user->forceFill(['two_factor_recovery_codes' => $remaining])->save();
            $this->completeLogin($request, $user);
            return redirect()->intended('/admin');
        }

        // Cek TOTP
        if ($svc->verify($user->two_factor_secret, $code)) {
            $this->completeLogin($request, $user);
            return redirect()->intended('/admin');
        }

        return back()->withErrors(['code' => 'Kode TOTP tidak valid.']);
    }

    protected function completeLogin(Request $request, $user): void
    {
        Auth::login($user, true);
        $request->session()->forget('2fa.user_id');
        $request->session()->put('2fa.verified', true);
        $user->update(['last_login_at' => now()]);
    }
}
