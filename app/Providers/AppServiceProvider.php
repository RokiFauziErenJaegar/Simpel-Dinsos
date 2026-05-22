<?php

namespace App\Providers;

use App\Models\Application;
use App\Observers\ApplicationAccessObserver;
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
    }
}
