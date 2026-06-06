<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Pengganti middleware Authenticate bawaan Filament.
 *
 * Filament default langsung `abort(403)` ketika user yang sudah login tapi
 * tidak boleh akses panel (canAccessPanel() === false) mencoba membuka /admin.
 * Untuk warga yang nyasar ke /admin (mis. karena url.intended tersimpan),
 * 403 mentah itu membingungkan. Subclass ini mengubahnya jadi redirect rapi:
 *
 *   - Warga          → diarahkan ke dashboard warga (sesi tetap hidup)
 *   - Akun internal tanpa akses (mis. is_active=false) → logout + ke login admin
 *
 * Pengecekan dilakukan tepat di titik otentikasi panel, jadi tidak ada masalah
 * urutan middleware (berbeda dengan pendekatan middleware terpisah yang kalah
 * prioritas dengan Authenticate bawaan Laravel).
 */
class AuthenticateAdminPanel extends FilamentAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if ($guard->check()) {
            $user = $guard->user();

            // Operator pekon punya area sendiri (/pekon) dan tidak boleh memakai
            // panel admin. Kredensialnya tetap valid untuk login, tapi setiap
            // request ke /admin dibelokkan ke dashboard pekon (sesi tetap hidup).
            if (($user->role ?? null) === UserRole::OperatorPekon) {
                throw new HttpResponseException(
                    redirect()->route('pekon.dashboard')
                );
            }

            $panel = Filament::getCurrentOrDefaultPanel();

            if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
                // Warga: arahkan ke area mereka, jangan cabut sesinya.
                if (($user->role ?? null) === UserRole::Warga) {
                    throw new HttpResponseException(
                        redirect()->route('warga.dashboard')
                    );
                }

                // Akun internal yang dinonaktifkan / tidak berhak: logout & balik ke login.
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new HttpResponseException(
                    redirect()->to(Filament::getLoginUrl())
                        ->with('error', 'Akun Anda tidak memiliki akses ke panel admin. Hubungi administrator.')
                );
            }
        }

        // User belum login atau berhak akses: serahkan ke perilaku bawaan Filament.
        parent::authenticate($request, $guards);
    }
}
