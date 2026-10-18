<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Aktif/nonaktifkan akun. Saat dinonaktifkan, semua token dicabut agar
     * sesi yang sedang berjalan ikut berhenti (R3).
     */
    public function ubahStatus(User $user, bool $aktif): User
    {
        return DB::transaction(function () use ($user, $aktif) {
            $user->update(['status_aktif' => $aktif]);

            if (! $aktif) {
                $user->tokens()->delete();
            }

            return $user;
        });
    }
}
