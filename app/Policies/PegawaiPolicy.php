<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Pegawai;
use App\Models\User;

/**
 * Akun internal hanya dikelola Manager (R2).
 */
class PegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Manager);
    }

    public function update(User $user, Pegawai $pegawai): bool
    {
        return $user->hasRole(Role::Manager);
    }
}
