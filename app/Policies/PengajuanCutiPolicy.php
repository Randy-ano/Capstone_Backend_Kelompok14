<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PengajuanCuti;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Cuti diputuskan HR Client dari klien tempat talent ditempatkan dan Manager; dibatalkan talent pemiliknya.
 */
class PengajuanCutiPolicy
{
    public function approve(User $user, PengajuanCuti $cuti): Response
    {
        $boleh = $user->hasRole(Role::Manager)
            || ($user->hasRole(Role::HrClient) && $cuti->placement->id_klien === $user->idKlien());

        return $boleh ? Response::allow() : Response::deny('Anda tidak memiliki akses ke pengajuan cuti ini.');
    }

    public function batal(User $user, PengajuanCuti $cuti): Response
    {
        return $cuti->id_talent === $user->talent?->id_talent
            ? Response::allow()
            : Response::deny('Anda hanya dapat membatalkan cuti milik Anda.');
    }
}
