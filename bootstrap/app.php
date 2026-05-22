<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            'role' => \App\Http\Middleware\EnsureWargaRole::class,
            '2fa' => \App\Http\Middleware\EnsureTwoFactor::class,
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
        ]);

        // Tenant resolver dijalankan di awal web & api stack
        $middleware->web(prepend: [\App\Http\Middleware\IdentifyTenant::class]);
        $middleware->api(prepend: [\App\Http\Middleware\IdentifyTenant::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
