<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Skip kalau bukan internal yang wajib 2FA
        if (! $user->twoFactorRequired()) {
            return $next($request);
        }

        // Skip pada route 2FA itu sendiri untuk hindari loop
        $route = $request->route()?->getName() ?? '';
        if (str_starts_with($route, 'two-factor.') || str_starts_with($route, 'warga.logout')) {
            return $next($request);
        }

        // Belum punya 2FA → paksa setup
        if (! $user->hasTwoFactorEnabled()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => '2FA setup required.', 'setup_url' => route('two-factor.show')], 403);
            }
            return redirect()->route('two-factor.show')->with('success',
                'Akun '.$user->role->label().' wajib mengaktifkan 2FA. Selesaikan setup di bawah ini.');
        }

        // Sudah enable tapi belum verifikasi di sesi ini → paksa challenge
        if (! $request->session()->get('2fa.verified')) {
            $request->session()->put('2fa.user_id', $user->id);
            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
