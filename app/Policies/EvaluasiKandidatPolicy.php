<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\EvaluasiKandidat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Evaluasi dilihat internal dan HR Client dari klien pemilik job.
 */
class EvaluasiKandidatPolicy
{
    public function view(User $user, EvaluasiKandidat $evaluasi): Response
    {
        $boleh = $user->isInternal()
            || ($user->hasRole(Role::HrClient) && $evaluasi->pengajuan->jobRequirement->id_klien === $user->idKlien());

        return $boleh ? Response::allow() : Response::deny('Anda tidak memiliki akses ke evaluasi ini.');
    }
}
