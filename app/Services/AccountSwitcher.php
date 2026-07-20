<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * Multi-akun petugas ala Instagram/WhatsApp.
 *
 * Ada dua lapis penyimpanan yang sengaja dipisah, dan bedanya penting:
 *
 *  1. SESI (`multi_account.ids`) — akun yang password-nya sudah diverifikasi
 *     pada sesi browser ini. HANYA akun di daftar ini yang boleh dipindahi
 *     tanpa password. Ikut hilang saat logout / sesi invalid.
 *
 *  2. COOKIE (`simpel_known_accounts`) — sekadar daftar id akun yang pernah
 *     dipakai di perangkat ini; bertahan 30 hari melewati logout. Isinya hanya
 *     jawaban atas "siapa saja yang pernah login di sini", BUKAN bukti
 *     otentikasi. Akun yang cuma ada di cookie tetap wajib password + 2FA.
 *
 * Konsekuensinya: cookie tidak pernah dipercaya sebagai kredensial. Semua id
 * (dari sesi maupun cookie) selalu divalidasi ulang ke DB — akun yang sudah
 * dinonaktifkan atau turun peran otomatis lenyap dari daftar.
 */
class AccountSwitcher
{
    /** Akun yang password-nya sudah diverifikasi di sesi ini. */
    public const SESSION_KEY = 'multi_account.ids';

    /** Akun yang sudah lolos challenge 2FA di sesi ini. */
    public const TWO_FACTOR_KEY = 'multi_account.2fa_verified_ids';

    public const COOKIE_NAME = 'simpel_known_accounts';

    public const COOKIE_DAYS = 30;

    /** Batas akun tertaut — menahan cookie membengkak di komputer loket bersama. */
    public const MAX_ACCOUNTS = 5;

    /**
     * Hanya peran internal (admin s/d petugas loket) yang boleh masuk switcher.
     * Warga punya area sendiri, dan operator pekon tidak memakai panel admin —
     * memasukkan keduanya cuma bikin user terlempar ke area lain saat pindah.
     */
    public function isLinkable(?User $user): bool
    {
        return $user !== null
            && $user->is_active
            && (bool) $user->role?->isInternal();
    }

    /**
     * Catat akun sebagai "sudah diverifikasi password di sesi ini" sekaligus
     * ingat di perangkat. Dipanggil dari listener event Login, jadi berlaku
     * untuk login lewat form Filament maupun lewat switcher.
     */
    public function link(User $user): void
    {
        if (! $this->isLinkable($user)) {
            return;
        }

        session()->put(self::SESSION_KEY, $this->capped(
            array_merge($this->sessionIds(), [$user->id])
        ));

        $this->remember($user);
    }

    /**
     * Pindah ke akun lain tanpa password. Pemanggil WAJIB memastikan lebih dulu
     * bahwa akun ini ada di daftar sesi (lihat canSwitchTo) — method ini sendiri
     * tidak memeriksa kredensial apa pun.
     */
    public function switchTo(User $user): void
    {
        Auth::login($user);
        $this->syncSessionPasswordHash($user);

        $user->forceFill(['last_login_at' => now()])->save();
    }

    /**
     * AuthenticateSession (stack panel) membandingkan `password_hash_web` di sesi
     * dengan password user yang sedang aktif, dan mencabut sesi kalau beda —
     * mekanisme "logout di semua perangkat saat password diganti". Auth::login()
     * tidak memperbarui nilai itu, jadi setiap kali user aktif berganti kita
     * wajib menyelaraskannya; kalau tidak, request berikutnya ke /admin dikira
     * password berubah dan petugas langsung terlempar keluar.
     */
    public function syncSessionPasswordHash(User $user): void
    {
        session()->put('password_hash_'.Auth::getDefaultDriver(), $user->getAuthPassword());
    }

    public function canSwitchTo(?User $user): bool
    {
        return $this->isLinkable($user)
            && in_array($user->id, $this->sessionIds(), true);
    }

    /**
     * Akun yang siap dipindahi seketika (tanpa password), selain yang aktif.
     */
    public function switchable(): Collection
    {
        return $this->hydrate($this->sessionIds())
            ->reject(fn (User $u): bool => $u->id === Auth::id())
            ->values();
    }

    /**
     * Akun yang pernah dipakai di perangkat ini tapi belum diverifikasi di sesi
     * ini — ditampilkan sebagai pintasan "lanjutkan sebagai ...", tetap wajib
     * password.
     */
    public function known(): Collection
    {
        $sessionIds = $this->sessionIds();

        return $this->hydrate($this->cookieIds())
            ->reject(fn (User $u): bool => $u->id === Auth::id() || in_array($u->id, $sessionIds, true))
            ->values();
    }

    /** Lepas akun dari sesi maupun dari ingatan perangkat. */
    public function forget(int $userId): void
    {
        session()->put(self::SESSION_KEY, array_values(array_diff($this->sessionIds(), [$userId])));
        session()->put(self::TWO_FACTOR_KEY, array_values(array_diff($this->twoFactorVerifiedIds(), [$userId])));

        $remaining = array_values(array_diff($this->cookieIds(), [$userId]));

        $remaining === []
            ? Cookie::queue(Cookie::forget(self::COOKIE_NAME))
            : $this->writeCookie($remaining);
    }

    public function markTwoFactorVerified(int $userId): void
    {
        session()->put(self::TWO_FACTOR_KEY, $this->capped(
            array_merge($this->twoFactorVerifiedIds(), [$userId])
        ));
    }

    /**
     * 2FA dilacak per akun, bukan satu flag untuk seluruh sesi. Kalau global,
     * petugas yang sudah 2FA lalu menambahkan akun kedua akan membawa akun baru
     * itu masuk panel tanpa pernah memasukkan kode TOTP-nya sendiri.
     */
    public function hasTwoFactorVerified(int $userId): bool
    {
        return in_array($userId, $this->twoFactorVerifiedIds(), true);
    }

    /** @return array<int, int> */
    public function sessionIds(): array
    {
        return $this->normalize(session(self::SESSION_KEY, []));
    }

    /** @return array<int, int> */
    public function twoFactorVerifiedIds(): array
    {
        return $this->normalize(session(self::TWO_FACTOR_KEY, []));
    }

    /** @return array<int, int> */
    public function cookieIds(): array
    {
        $raw = request()->cookie(self::COOKIE_NAME);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $this->normalize($decoded) : [];
    }

    protected function remember(User $user): void
    {
        $this->writeCookie($this->capped(array_merge($this->cookieIds(), [$user->id])));
    }

    protected function writeCookie(array $ids): void
    {
        Cookie::queue(Cookie::make(
            name: self::COOKIE_NAME,
            value: json_encode(array_values($ids)),
            minutes: self::COOKIE_DAYS * 24 * 60,
            httpOnly: true,
            sameSite: 'lax',
        ));
    }

    /**
     * Id → User, sekaligus gerbang terakhir: apa pun isi sesi/cookie, yang lolos
     * hanya akun yang saat ini masih benar-benar berhak.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, User>
     */
    protected function hydrate(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $users = User::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?User => $users->get($id))
            ->filter(fn (?User $u): bool => $this->isLinkable($u))
            ->values();
    }

    /**
     * Buang duplikat & non-integer, lalu simpan hanya MAX_ACCOUNTS terbaru.
     *
     * @return array<int, int>
     */
    protected function capped(array $ids): array
    {
        return array_values(array_slice($this->normalize($ids), -self::MAX_ACCOUNTS));
    }

    /** @return array<int, int> */
    protected function normalize(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map(
            intval(...),
            array_filter($ids, fn ($id): bool => is_int($id) || (is_string($id) && ctype_digit($id))),
        )));
    }
}
