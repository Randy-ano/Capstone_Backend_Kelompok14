<?php

namespace App\Policies;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Notifikasi hanya bisa diakses penerimanya.
 */
class NotifikasiPolicy
{
    public function update(User $user, Notifikasi $notifikasi): Response
    {
        return $notifikasi->id_user_penerima === $user->id_user
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke notifikasi ini.');
    }
}
