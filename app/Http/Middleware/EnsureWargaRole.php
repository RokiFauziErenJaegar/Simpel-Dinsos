<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWargaRole
{
    public function handle(Request $request, Closure $next, string $role = 'warga'): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('warga.login');
        }

        $required = UserRole::tryFrom($role);
        if (! $required || $user->role !== $required) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
