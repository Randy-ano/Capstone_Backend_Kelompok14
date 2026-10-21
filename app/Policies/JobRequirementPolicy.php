<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\JobRequirement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Job requirement: internal melihat semua; HR Client hanya milik kliennya.
 * Dibuat/diubah oleh HR Client (kliennya) dan AM; rekomendasi khusus TS.
 */
class JobRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal() || $user->hasRole(Role::HrClient);
    }

    public function view(User $user, JobRequirement $job): Response
    {
        return $user->isInternal() || $this->milikKliennya($user, $job)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke job requirement ini.');
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::HrClient, Role::AccountManager);
    }

    public function update(User $user, JobRequirement $job): Response
    {
        if ($user->hasRole(Role::AccountManager) || $this->milikKliennya($user, $job)) {
            return Response::allow();
        }

        return Response::deny($user->hasRole(Role::HrClient)
            ? 'Anda tidak memiliki akses ke job requirement ini.'
            : 'Hanya HR Client dan Account Manager yang dapat mengubah job requirement.');
    }

    public function rekomendasi(User $user, JobRequirement $job): Response
    {
        return $user->hasRole(Role::TalentSupport)
            ? Response::allow()
            : Response::deny('Hanya Talent Support yang dapat melihat rekomendasi kandidat.');
    }

    private function milikKliennya(User $user, JobRequirement $job): bool
    {
        return $user->hasRole(Role::HrClient) && $user->idKlien() === $job->id_klien;
    }
}
