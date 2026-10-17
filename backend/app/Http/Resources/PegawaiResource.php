<?php

namespace App\Http\Resources;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pegawai
 */
class PegawaiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pegawai' => $this->id_pegawai,
            'nama_pegawai' => $this->nama_pegawai,
            'jabatan' => $this->jabatan,
            'no_telepon' => $this->no_telepon,
            'akun' => $this->whenLoaded('user', fn () => [
                'id_user' => $this->user->id_user,
                'email' => $this->user->email,
                'role' => $this->user->role,
                'status_aktif' => $this->user->status_aktif,
            ]),
        ];
    }
}
