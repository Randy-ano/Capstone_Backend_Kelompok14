<?php

namespace App\Http\Resources;

use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Talent
 */
class TalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_talent' => $this->id_talent,
            'nama_lengkap' => $this->nama_lengkap,
            'pendidikan' => $this->pendidikan,
            'pengalaman_kerja' => $this->pengalaman_kerja,
            'status_kelengkapan' => $this->status_kelengkapan,
            'status_kandidat' => $this->status_kandidat,
            'ada_cv' => $this->file_cv !== null,
        ];
    }
}
