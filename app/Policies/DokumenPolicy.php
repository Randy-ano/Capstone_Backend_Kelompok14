<?php

namespace App\Policies;

use App\Models\Dokumen;
use App\Models\User;
use App\Services\DokumenService;
use Illuminate\Auth\Access\Response;

/**
 * Dokumen: dilihat/diunduh sesuai scope (DokumenService::scope); penandatangan diatur pengunggah;
 * tanda tangan oleh user yang terdaftar sebagai penandatangan.
 */
class DokumenPolicy
{
    public function view(User $user, Dokumen $dokumen): Response
    {
        return DokumenService::bolehLihat($user, $dokumen)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke dokumen ini.');
    }

    public function aturPenandatangan(User $user, Dokumen $dokumen): Response
    {
        return $dokumen->id_user_uploader === $user->id_user
            ? Response::allow()
            : Response::deny('Hanya pengunggah dokumen yang dapat menentukan penandatangan.');
    }

    public function tandaTangan(User $user, Dokumen $dokumen): Response
    {
        return $dokumen->tandaTangan()->where('id_user_penandatangan', $user->id_user)->exists()
            ? Response::allow()
            : Response::deny('Anda bukan penandatangan dokumen ini.');
    }
}
