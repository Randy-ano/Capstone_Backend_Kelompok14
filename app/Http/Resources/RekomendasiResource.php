<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu kandidat rekomendasi (R9). Resource berupa array dari RekomendasiService::daftar():
 * ['talent' => Talent, 'skor_kecocokan' => ..., ...].
 */
class RekomendasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $talent = $this->resource['talent'];

        return [
            'id_talent' => $talent->id_talent,
            'id_talent_pool' => $talent->talentPool?->id_talent_pool,
            'nama_lengkap' => $talent->nama_lengkap,
            'pendidikan' => $talent->ringkasanPendidikan(),
            'skor_kecocokan' => $this->resource['skor_kecocokan'],
            'skor_kompetensi' => $this->resource['skor_kompetensi'],
            'skor_pengalaman' => $this->resource['skor_pengalaman'],
            'lama_pengalaman' => $this->resource['lama_pengalaman'],
            'kompetensi_cocok' => $this->resource['kompetensi_cocok'],
            'kompetensi_kurang' => $this->resource['kompetensi_kurang'],
            'status_ketersediaan' => $talent->talentPool?->status_ketersediaan,
            'tag_keahlian' => $talent->talentPool?->tag_keahlian,
        ];
    }
}
