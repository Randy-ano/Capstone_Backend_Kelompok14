<?php

namespace App\Http\Resources;

use App\Models\TalentPool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Item daftar talent pool. Detail memakai TalentPoolDetailResource.
 *
 * @mixin TalentPool
 */
class TalentPoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $talent = $this->talent;

        return [
            'id_talent_pool' => $this->id_talent_pool,
            'tanggal_masuk' => $this->tanggal_masuk?->toIso8601String(),
            'status_ketersediaan' => $this->status_ketersediaan,
            'tag_keahlian' => $this->tag_keahlian,
            'talent' => [
                'id_talent' => $talent->id_talent,
                'nama_lengkap' => $talent->nama_lengkap,
                'email' => $talent->user?->email,
                'domisili' => $talent->domisili,
                'pendidikan' => $talent->ringkasanPendidikan(),
                'status_kandidat' => $talent->status_kandidat,
                'kompetensi' => KompetensiResource::collection($talent->kompetensi),
                'ada_cv' => $talent->file_cv !== null,
            ],
        ];
    }
}
