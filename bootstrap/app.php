<?php

use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\EnsureWargaRole;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureWargaRole::class,
            '2fa' => EnsureTwoFactor::class,
            'tenant' => IdentifyTenant::class,
            // Alias ability Sanctum (tidak terdaftar otomatis di Laravel 12)
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Percaya proxy (Railway/Cloudflare) agar HTTPS, host, & IP klien terdeteksi benar.
        $middleware->trustProxies(at: '*');

        // Tenant resolver dijalankan di awal web & api stack
        $middleware->web(prepend: [IdentifyTenant::class]);
        $middleware->api(prepend: [IdentifyTenant::class]);

        // Security headers global pada respons web
        $middleware->web(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Untuk request API/JSON, kembalikan 404 JSON konsisten (bukan HTML) saat
        // model tidak ditemukan (mis. firstOrFail pada endpoint by-code).
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            return null;
        });
    })->create();
