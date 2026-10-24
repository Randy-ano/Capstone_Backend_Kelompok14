<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\JadwalInterview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Jadwal interview dibuat & diubah (termasuk ditandai selesai/dibatalkan) hanya oleh Account Manager (R16).
 * HR Client & talent hanya melihat.
 */
class JadwalInterviewPolicy
{
    public function update(User $user, JadwalInterview $interview): Response
    {
        return $user->hasRole(Role::AccountManager) ? Response::allow() : Response::deny('Anda tidak memiliki akses ke jadwal interview ini.');
    }
}
