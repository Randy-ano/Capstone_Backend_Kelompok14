<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Fitur operasional & HR (cuti, shift, dokumen) untuk role talent hanya terbuka setelah talent punya
 * penempatan berstatus Belum Aktif atau Aktif. Role lain tidak terpengaruh. Dipasang setelah auth:sanctum.
 */
class EnsureTalentDitempatkan
{
    public const PESAN = 'Fitur ini tersedia setelah Anda ditempatkan di perusahaan klien.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole(Role::Talent) && ! $user->talent?->sudahDitempatkan()) {
            throw new AccessDeniedHttpException(self::PESAN);
        }

        return $next($request);
    }
}
