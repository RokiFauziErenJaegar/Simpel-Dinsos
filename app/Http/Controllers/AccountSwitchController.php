<?php

namespace App\Http\Controllers;

use App\Models\DataAccessLog;
use App\Models\User;
use App\Services\AccountSwitcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Multi-akun petugas: tambah akun, pindah akun, lepas akun.
 *
 * Route-nya hanya bermiddleware `auth` — sengaja TIDAK dipasangi gerbang 2FA.
 * Petugas yang baru login dan masih tertahan di challenge 2FA tetap harus bisa
 * pindah ke akun lain yang sudah terverifikasi (justru itu gunanya fitur ini).
 * Tidak ada celah: gerbang 2FA tetap berlaku di panel, per akun.
 *
 * @see AccountSwitcher untuk pemisahan sesi (boleh pindah) vs cookie (wajib password).
 */
class AccountSwitchController extends Controller
{
    public function __construct(protected AccountSwitcher $switcher) {}

    /** Form login untuk menambahkan akun kedua tanpa mengeluarkan akun aktif. */
    public function create(Request $request)
    {
        return view('public.akun.tambah', [
            'current' => $request->user(),
            'switchable' => $this->switcher->switchable(),
            'known' => $this->switcher->known(),
            'prefill' => $this->prefillEmail($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureNotRateLimited($request, $data['email']);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request, $data['email']), 300);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        if (! $this->switcher->isLinkable($user)) {
            RateLimiter::hit($this->throttleKey($request, $data['email']), 300);

            throw ValidationException::withMessages([
                'email' => $user->is_active
                    ? 'Akun ini bukan akun petugas, jadi tidak bisa ditambahkan ke panel.'
                    : 'Akun ini sedang dinonaktifkan. Hubungi administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $data['email']));

        if ($user->id === $request->user()?->id) {
            return redirect('/admin');
        }

        DataAccessLog::record('account.add', $user, reason: 'Tambah akun ke switcher multi-akun');

        $this->switcher->link($user);
        $this->switcher->switchTo($user);
        $request->session()->regenerateToken();

        return redirect('/admin')->with('success', 'Masuk sebagai '.$user->name.'.');
    }

    /** Pindah ke akun yang sudah diverifikasi di sesi ini — tanpa password. */
    public function switch(Request $request, int $userId)
    {
        $user = User::find($userId);

        if (! $this->switcher->canSwitchTo($user)) {
            // Akun belum diverifikasi di sesi ini (mis. cuma tersimpan di cookie
            // perangkat, atau baru saja dinonaktifkan admin) → wajib password.
            return redirect()
                ->route('account.add', $user ? ['email' => $user->email] : [])
                ->with('error', 'Masukkan kata sandi untuk melanjutkan ke akun tersebut.');
        }

        DataAccessLog::record('account.switch', $user, reason: 'Pindah akun tanpa login ulang');

        $this->switcher->switchTo($user);
        $request->session()->regenerateToken();

        return redirect('/admin')->with('success', 'Sekarang aktif sebagai '.$user->name.'.');
    }

    /** Lepas akun dari daftar sesi + ingatan perangkat. */
    public function forget(Request $request, int $userId)
    {
        // Melepas akun yang sedang aktif = logout; arahkan ke jalur logout resmi
        // Filament agar sesi benar-benar dicabut, bukan cuma dihapus dari daftar.
        if ($userId === $request->user()?->id) {
            return back()->with('error', 'Untuk melepas akun yang sedang aktif, gunakan menu Keluar.');
        }

        $this->switcher->forget($userId);

        return back()->with('success', 'Akun dilepas dari perangkat ini.');
    }

    protected function prefillEmail(Request $request): ?string
    {
        $email = $request->query('email');

        if (! is_string($email)) {
            return null;
        }

        // Hanya terima email yang memang milik akun tertaut, supaya parameter URL
        // tidak bisa dipakai menaruh teks sembarangan di form login.
        $isKnown = $this->switcher->known()->merge($this->switcher->switchable())
            ->contains(fn (User $u): bool => $u->email === $email);

        return $isKnown ? $email : null;
    }

    protected function ensureNotRateLimited(Request $request, string $email): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $email), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan. Coba lagi dalam '
                .ceil(RateLimiter::availableIn($this->throttleKey($request, $email)) / 60).' menit.',
        ]);
    }

    protected function throttleKey(Request $request, string $email): string
    {
        return 'account-add:'.mb_strtolower($email).'|'.$request->ip();
    }
}
