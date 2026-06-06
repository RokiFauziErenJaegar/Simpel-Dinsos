<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

/**
 * Read-only: tidak menulis DB, hanya actingAs user yang sudah ada + GET.
 * Memverifikasi warga tidak lagi kena 403 saat menyentuh /admin.
 */
class AdminPanelAccessTest extends TestCase
{
    public function test_warga_diarahkan_ke_dashboard_bukan_403(): void
    {
        $warga = User::where('role', UserRole::Warga->value)->first();
        $this->assertNotNull($warga, 'Butuh minimal 1 user warga di DB untuk test ini.');

        $response = $this->actingAs($warga)->get('/admin');

        $response->assertRedirect(route('warga.dashboard'));
    }

    public function test_operator_pekon_dibelokkan_ke_dashboard_pekon(): void
    {
        $operator = User::where('role', UserRole::OperatorPekon->value)->first();
        $this->assertNotNull($operator, 'Butuh minimal 1 user operator_pekon di DB untuk test ini.');

        $response = $this->actingAs($operator)->get('/admin');

        $response->assertRedirect(route('pekon.dashboard'));
    }

    public function test_user_internal_aktif_tidak_kena_403(): void
    {
        $internal = User::whereIn('role', ['admin', 'petugas', 'kadis', 'kabid', 'kasi', 'sekretaris'])
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($internal, 'Butuh minimal 1 user internal aktif di DB untuk test ini.');

        $response = $this->actingAs($internal)->get('/admin');

        // Boleh 200 (panel) atau 302 (mis. dipaksa setup 2FA), yang penting BUKAN 403.
        $this->assertNotSame(403, $response->status(), 'User internal aktif seharusnya tidak kena 403.');
    }

    public function test_tamu_diarahkan_ke_login_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('filament.admin.auth.login'));
    }
}
