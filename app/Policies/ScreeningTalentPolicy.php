<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ScreeningTalent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Screening dilihat TS, AM, Manager; dibuat dan diubah khusus Talent Support.
 */
class ScreeningTalentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    public function create(User $user): Response
    {
        return $this->khususTs($user);
    }

    public function update(User $user, ScreeningTalent $screening): Response
    {
        return $this->khususTs($user);
    }

    private function khususTs(User $user): Response
    {
        return $user->hasRole(Role::TalentSupport)
            ? Response::allow()
            : Response::deny('Hanya Talent Support yang dapat melakukan screening.');
    }
}
