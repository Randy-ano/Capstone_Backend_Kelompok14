<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Manager boleh mengaktifkan/menonaktifkan akun lain, tapi tidak akunnya sendiri
     * (mencegah sistem kehilangan semua Manager aktif secara tidak sengaja).
     */
    public function updateStatus(User $user, User $target): Response
    {
        if (! $user->hasRole(Role::Manager)) {
            return Response::deny();
        }

        return $user->is($target)
            ? Response::deny('Anda tidak dapat mengubah status akun Anda sendiri.')
            : Response::allow();
    }
}
