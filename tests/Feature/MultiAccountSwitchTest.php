<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AccountSwitcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAccountSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function petugas(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Petugas,
            'is_active' => true,
        ], $attributes));
    }

    /** Akun dengan 2FA sudah aktif & terkonfirmasi. */
    protected function petugasWithTwoFactor(): User
    {
        return $this->petugas([
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_tambah_akun_dengan_password_benar_mengaktifkan_akun_baru(): void
    {
        $a = $this->petugas();
        $b = $this->petugas();

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id]])
            ->post(route('account.add.store'), [
                'email' => $b->email,
                'password' => 'password',
            ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($b);

        // Akun lama tidak boleh ikut terlempar keluar — itu inti fiturnya.
        $this->assertEqualsCanonicalizing([$a->id, $b->id], session(AccountSwitcher::SESSION_KEY));
    }

    public function test_pindah_ke_akun_yang_sudah_terverifikasi_tidak_perlu_password(): void
    {
        $a = $this->petugas();
        $b = $this->petugas();

        $response = $this->actingAs($b)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id, $b->id]])
            ->post(route('account.switch', $a->id));

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($a);
    }

    public function test_pindah_ke_akun_yang_belum_terverifikasi_di_sesi_ini_ditolak(): void
    {
        $a = $this->petugas();
        $b = $this->petugas();

        // b hanya "dikenal perangkat" (cookie), bukan terverifikasi di sesi ini.
        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id]])
            ->withCookie(AccountSwitcher::COOKIE_NAME, json_encode([$a->id, $b->id]))
            ->post(route('account.switch', $b->id));

        $response->assertRedirect(route('account.add', ['email' => $b->email]));
        $this->assertAuthenticatedAs($a);
    }

    public function test_sesi_yang_dipalsukan_tidak_bisa_memindahkan_ke_akun_nonaktif(): void
    {
        $a = $this->petugas();
        $b = $this->petugas(['is_active' => false]);

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id, $b->id]])
            ->post(route('account.switch', $b->id));

        $response->assertRedirect(route('account.add', ['email' => $b->email]));
        $this->assertAuthenticatedAs($a);
    }

    public function test_akun_warga_tidak_bisa_ditambahkan_ke_switcher_petugas(): void
    {
        $a = $this->petugas();
        $warga = User::factory()->create([
            'role' => UserRole::Warga,
            'is_active' => true,
        ]);

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id]])
            ->post(route('account.add.store'), [
                'email' => $warga->email,
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertAuthenticatedAs($a);
    }

    public function test_password_salah_ditolak_dan_akun_aktif_tidak_berubah(): void
    {
        $a = $this->petugas();
        $b = $this->petugas();

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id]])
            ->post(route('account.add.store'), [
                'email' => $b->email,
                'password' => 'salah-total',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertAuthenticatedAs($a);
        $this->assertSame([$a->id], session(AccountSwitcher::SESSION_KEY));
    }

    /**
     * Inti keamanan fitur ini: 2FA dilacak per akun. Kalau flag-nya global
     * per-sesi, akun kedua ikut menumpang 2FA milik akun pertama dan masuk panel
     * tanpa pernah memasukkan kode TOTP-nya sendiri.
     */
    public function test_akun_kedua_tetap_kena_challenge_2fa_meski_akun_pertama_sudah_verifikasi(): void
    {
        $a = $this->petugasWithTwoFactor();
        $b = $this->petugasWithTwoFactor();

        $response = $this->actingAs($b)
            ->withSession([
                AccountSwitcher::SESSION_KEY => [$a->id, $b->id],
                AccountSwitcher::TWO_FACTOR_KEY => [$a->id], // hanya a yang lolos
            ])
            ->get('/admin');

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertSame($b->id, session('2fa.user_id'));
    }

    public function test_akun_yang_sudah_verifikasi_2fa_tidak_diminta_challenge_lagi(): void
    {
        $a = $this->petugasWithTwoFactor();

        $response = $this->actingAs($a)
            ->withSession([
                AccountSwitcher::SESSION_KEY => [$a->id],
                AccountSwitcher::TWO_FACTOR_KEY => [$a->id],
            ])
            ->get('/admin');

        $response->assertOk();
    }

    public function test_lepas_akun_menghapus_dari_sesi_dan_cookie(): void
    {
        $a = $this->petugas();
        $b = $this->petugas();

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id, $b->id]])
            ->withCookie(AccountSwitcher::COOKIE_NAME, json_encode([$a->id, $b->id]))
            ->from(route('account.add'))
            ->post(route('account.forget', $b->id));

        $response->assertRedirect(route('account.add'));
        $this->assertSame([$a->id], session(AccountSwitcher::SESSION_KEY));
        $response->assertCookie(AccountSwitcher::COOKIE_NAME, json_encode([$a->id]));
    }

    public function test_akun_aktif_tidak_bisa_dilepas_lewat_switcher(): void
    {
        $a = $this->petugas();

        $response = $this->actingAs($a)
            ->withSession([AccountSwitcher::SESSION_KEY => [$a->id]])
            ->from(route('account.add'))
            ->post(route('account.forget', $a->id));

        $response->assertRedirect(route('account.add'));
        $this->assertSame([$a->id], session(AccountSwitcher::SESSION_KEY));
        $this->assertAuthenticatedAs($a);
    }

    public function test_tamu_tidak_bisa_menyentuh_route_multi_akun(): void
    {
        $b = $this->petugas();

        $this->post(route('account.switch', $b->id))->assertRedirect();
        $this->assertGuest();
    }
}
