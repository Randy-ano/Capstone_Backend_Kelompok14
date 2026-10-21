<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SertifikatTalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_sertifikat' => $this->id_sertifikat,
            'nama_sertifikat' => $this->nama_sertifikat,
            'penerbit' => $this->penerbit,

            'tanggal_terbit' => $this->tanggal_terbit?->format('Y-m-d'),
            'tanggal_kedaluwarsa' => $this->tanggal_kedaluwarsa?->format('Y-m-d'),

            'file_sertifikat' => $this->file_sertifikat,
            'url_sertifikat' => $this->url_sertifikat,
        ];
    }
}
