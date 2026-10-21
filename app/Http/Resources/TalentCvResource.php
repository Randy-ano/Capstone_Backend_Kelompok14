<?php

namespace App\Http\Resources;

use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Path file tidak ditampilkan (disk private); CV diunduh lewat endpoint terproteksi.
 *
 * @mixin Talent
 */
class TalentCvResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Relasi dokumen dimuat TalentCvService, sudah difilter jenis cv.
        $dokumen = $this->relationLoaded('dokumen') ? $this->dokumen->first() : null;

        return [
            'id_talent' => $this->id_talent,
            'nama_lengkap' => $this->nama_lengkap,
            'ada_cv' => $this->file_cv !== null,
            'id_dokumen' => $dokumen?->id_dokumen,
            'tanggal_dibuat' => $dokumen?->tanggal_unggah?->toIso8601String(),
        ];
    }
}
