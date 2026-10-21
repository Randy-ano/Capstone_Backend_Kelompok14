<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\PengajuanKandidat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Pengajuan: internal semua; HR Client hanya ke job kliennya; talent hanya miliknya.
 * Status pengajuan ditinjau HR Client klien pemilik job (R15).
 */
class PengajuanKandidatPolicy
{
    public function view(User $user, PengajuanKandidat $pengajuan): Response
    {
        $boleh = match (true) {
            $user->isInternal() => true,
            $user->hasRole(Role::HrClient) => $pengajuan->jobRequirement->id_klien === $user->idKlien(),
            $user->hasRole(Role::Talent) => $pengajuan->id_talent === $user->talent?->id_talent,
            default => false,
        };

        return $boleh ? Response::allow() : Response::deny('Anda tidak memiliki akses ke pengajuan ini.');
    }

    public function updateStatus(User $user, PengajuanKandidat $pengajuan): Response
    {
        return $user->hasRole(Role::HrClient) && $pengajuan->jobRequirement->id_klien === $user->idKlien()
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke pengajuan ini.');
    }
}
