<?php

namespace App\Policies;

use App\Models\SertifikatTalent;
use App\Models\User;
use App\Services\SertifikatService;
use Illuminate\Auth\Access\Response;

/**
 * File sertifikat: diunggah hanya oleh talent pemiliknya; dilihat oleh pemilik, internal (AM, TS, Manager),
 * dan HR Client yang pernah menerima pengajuan talent tersebut.
 */
class SertifikatTalentPolicy
{
    public function unggahFile(User $user, SertifikatTalent $sertifikat): Response
    {
        return $user->talent?->id_talent === $sertifikat->id_talent
            ? Response::allow()
            : Response::deny('Anda hanya dapat mengunggah file untuk sertifikat milik Anda sendiri.');
    }

    public function lihatFile(User $user, SertifikatTalent $sertifikat): Response
    {
        return SertifikatService::bolehLihat($user, $sertifikat)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke file sertifikat ini.');
    }
}
