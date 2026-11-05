<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ScreeningTalent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Usulan kandidat (screening) dilihat TS, AM, Manager; dibuat dan diubah Talent Support; ditinjau Account Manager.
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

    /** AM menolak meneruskan usulan ke klien (meneruskan = POST /pengajuan). */
    public function tinjau(User $user, ScreeningTalent $screening): Response
    {
        return $user->hasRole(Role::AccountManager)
            ? Response::allow()
            : Response::deny('Hanya Account Manager yang dapat meninjau usulan kandidat.');
    }

    private function khususTs(User $user): Response
    {
        return $user->hasRole(Role::TalentSupport)
            ? Response::allow()
            : Response::deny('Hanya Talent Support yang dapat mengusulkan kandidat.');
    }
}
