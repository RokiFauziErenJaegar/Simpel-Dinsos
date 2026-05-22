<?php

namespace App\Observers;

use App\Models\Application;
use App\Models\DataAccessLog;

class ApplicationAccessObserver
{
    /** Cache decision per-request agar tidak panggil request() berkali-kali. */
    protected static ?bool $shouldLog = null;

    public function retrieved(Application $application): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (static::$shouldLog === false) {
            return; // decision sudah diambil di request ini
        }

        if (static::$shouldLog === null) {
            static::$shouldLog = $this->resolveShouldLog();
            if (! static::$shouldLog) return;
        }

        DataAccessLog::record(
            action: 'view',
            subject: $application,
            ownerNik: $application->beneficiary_nik,
        );
    }

    /**
     * Tentukan sekali per request apakah akses model perlu di-log:
     * - Bukan request console
     * - Bukan list/index/livewire (banyak record)
     * - Bukan dari API/webhook (sudah ada audit lain)
     * - User adalah pegawai (bukan warga / anonymous)
     */
    protected function resolveShouldLog(): bool
    {
        $req = request();
        if (! $req) return false;

        $route = $req->route()?->getName() ?? '';
        // Skip list, table livewire, json api
        if (str_contains($route, 'index')
            || str_starts_with($req->path(), 'livewire/')
            || str_starts_with($req->path(), 'api/')
            || str_starts_with($req->path(), 'filament/')
            || $req->expectsJson()) {
            return false;
        }

        $user = $req->user();
        if (! $user) return false;

        return method_exists($user, 'isWarga') ? ! $user->isWarga() : false;
    }
}
