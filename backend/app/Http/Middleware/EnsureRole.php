<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Membatasi route untuk role tertentu, contoh: middleware('role:talent_support,manager').
 * Dipasang setelah auth:sanctum.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        if (! in_array($user->role->value, $roles, true)) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke fitur ini.');
        }

        return $next($request);
    }
}
