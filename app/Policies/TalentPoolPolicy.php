<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Talent pool dilihat semua role internal; hanya Talent Support yang mengubahnya.
 */
class TalentPoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    public function view(User $user, TalentPool $talentPool): bool
    {
        return $user->isInternal();
    }

    public function update(User $user, TalentPool $talentPool): Response
    {
        return $user->hasRole(Role::TalentSupport)
            ? Response::allow()
            : Response::deny('Hanya Talent Support yang dapat mengubah data talent pool.');
    }
}
