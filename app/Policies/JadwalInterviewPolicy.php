<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\JadwalInterview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Jadwal interview diubah oleh AM, atau HR Client dari klien pemilik job (R16).
 */
class JadwalInterviewPolicy
{
    public function update(User $user, JadwalInterview $interview): Response
    {
        $boleh = $user->hasRole(Role::AccountManager)
            || ($user->hasRole(Role::HrClient) && $interview->pengajuan->jobRequirement->id_klien === $user->idKlien());

        return $boleh ? Response::allow() : Response::deny('Anda tidak memiliki akses ke jadwal interview ini.');
    }
}
