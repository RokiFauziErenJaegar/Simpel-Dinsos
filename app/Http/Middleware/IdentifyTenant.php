<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Identifikasi tenant aktif berdasarkan:
     *   1. Subdomain (mis. pringsewu.simpel-dinsos.id)
     *   2. Header X-Tenant (untuk API mobile)
     *   3. Default dari config (mode single)
     *
     * Hasil di-cache 5 menit untuk hindari query DB setiap request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('tenant.mode', 'single');

        $tenant = null;

        if ($mode === 'single') {
            $slug = config('tenant.current.id');
            $tenant = Cache::remember(
                "tenant.{$slug}",
                300, // 5 menit
                fn () => Tenant::where('slug', $slug)->first()
            );
        } elseif ($mode === 'shared-db' || $mode === 'per-db') {
            $host = $request->getHost();
            $headerTenant = $request->header('X-Tenant');
            $cacheKey = 'tenant.host.'.md5($host.'|'.$headerTenant);

            $tenant = Cache::remember($cacheKey, 300, function () use ($request, $headerTenant) {
                $t = Tenant::fromSubdomain($request->getHost());
                if (! $t && $headerTenant) {
                    $t = Tenant::where('slug', $headerTenant)->where('is_active', true)->first();
                }

                return $t;
            });

            if (! $tenant) {
                abort(404, 'Tenant tidak ditemukan untuk host: '.$request->getHost());
            }

            if ($mode === 'per-db' && ($tenant->settings['db_connection'] ?? null)) {
                config(['database.default' => $tenant->settings['db_connection']]);
            }
        }

        if ($tenant) {
            app()->instance('tenant.current', $tenant);
            config([
                'tenant.current.name' => $tenant->name,
                'tenant.current.instansi' => $tenant->instansi,
            ]);
        }

        return $next($request);
    }
}
