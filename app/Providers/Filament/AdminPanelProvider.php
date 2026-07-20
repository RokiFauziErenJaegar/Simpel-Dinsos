<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\KadisOverview;
use App\Filament\Admin\Widgets\KieOverview;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\EnsureTwoFactor;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Multi-akun petugas: pemilih akun di topbar, dan pintasan akun yang
        // pernah dipakai di halaman login.
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): string => view('filament.account-switcher')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): string => view('filament.login-known-accounts')->render(),
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('SIMPEL DINSOS')
            ->brandLogo(null)
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Emerald,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Pelayanan',
                'Master',
                'Pengaturan',
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                KadisOverview::class,
                KieOverview::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                // Subclass Authenticate: warga/akun tanpa akses di-redirect rapi,
                // bukan 403 Forbidden. Lihat AuthenticateAdminPanel.
                AuthenticateAdminPanel::class,
                EnsureTwoFactor::class,
            ]);
    }
}
