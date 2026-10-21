<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Kompetensi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Master kompetensi dibaca semua role; dikelola Talent Support dan Manager.
 */
class KompetensiPolicy
{
    public function create(User $user): Response
    {
        return $this->pengelola($user);
    }

    public function update(User $user, Kompetensi $kompetensi): Response
    {
        return $this->pengelola($user);
    }

    public function delete(User $user, Kompetensi $kompetensi): Response
    {
        return $this->pengelola($user);
    }

    private function pengelola(User $user): Response
    {
        return $user->hasRole(Role::TalentSupport, Role::Manager)
            ? Response::allow()
            : Response::deny('Hanya Talent Support dan Manager yang dapat mengelola master kompetensi.');
    }
}
