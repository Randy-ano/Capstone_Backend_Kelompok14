<?php

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_user' => $this->id_user,
            'email' => $this->email,
            'role' => $this->role,
            'status_aktif' => $this->status_aktif,
            'profil' => $this->profilResource(),
        ];
    }

    private function profilResource(): ?JsonResource
    {
        $profil = $this->resource->profil();

        if ($profil === null) {
            return null;
        }

        return match ($this->role) {
            Role::Talent => new TalentResource($profil),
            Role::HrClient => new HrClientResource($profil->loadMissing('klien')),
            default => new PegawaiResource($profil),
        };
    }
}
