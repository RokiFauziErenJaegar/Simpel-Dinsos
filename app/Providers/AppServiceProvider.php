<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\User;
use App\Observers\ApplicationAccessObserver;
use App\Services\AccountSwitcher;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Audit log: catat setiap akses baca data Application oleh internal
        Application::observe(ApplicationAccessObserver::class);

        // Multi-akun: setiap login yang berhasil (form Filament, switcher, atau
        // penyelesaian 2FA) mendaftarkan akunnya ke switcher. Disambungkan ke
        // event Login — bukan ke satu controller — supaya tidak ada jalur login
        // yang terlewat dan diam-diam bikin akun hilang dari daftar.
        Event::listen(function (Login $event): void {
            if ($event->user instanceof User) {
                app(AccountSwitcher::class)->link($event->user);
            }
        });
    }
}
