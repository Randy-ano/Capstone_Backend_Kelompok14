<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Fase 10: dipakai saat `php artisan scribe:generate`. Sebelum setiap response call, login sebagai akun demo
 * yang berhak atas route tersebut (irisan semua middleware `role:`), agar contoh respons di dokumentasi berasal
 * dari data seed asli — bukan 401/403. Token dibuat di dalam transaksi Scribe sehingga ikut di-rollback.
 */
class DokumentasiApi
{
    /** Akun demo per role (seeder). */
    public const AKUN_DEMO = [
        'manager' => 'manager@intellisys.test',
        'account_manager' => 'am@intellisys.test',
        'talent_support' => 'ts@intellisys.test',
        'hr_client' => 'hr@intellisys.test',
        'talent' => 'talent@intellisys.test',
    ];

    public static function sebelumResponseCall(Request $request, Route $route): void
    {
        if (! in_array('auth:sanctum', $route->gatherMiddleware(), true)) {
            return;
        }

        // Guard menyimpan user dari panggilan sebelumnya dalam satu proses; reset agar token baru dipakai.
        auth()->forgetGuards();

        $user = User::where('email', self::AKUN_DEMO[self::roleUntuk($route)])->first();
        if ($user) {
            $request->headers->set('Authorization', 'Bearer '.$user->createToken('dokumentasi-api')->plainTextToken);
        }
    }

    /**
     * Role pertama yang lolos semua middleware `role:` route; Manager jika route tidak dibatasi role.
     */
    public static function roleUntuk(Route $route): string
    {
        $boleh = array_map(fn (Role $r) => $r->value, Role::cases());
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:')) {
                $boleh = array_values(array_intersect($boleh, explode(',', substr($middleware, 5))));
            }
        }

        $urutan = array_keys(self::AKUN_DEMO);
        $pilihan = array_values(array_intersect($urutan, $boleh));

        return $pilihan[0] ?? 'manager';
    }
}
