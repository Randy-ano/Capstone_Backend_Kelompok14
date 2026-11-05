<?php

namespace App\Http\Resources;

use App\Services\SertifikatService;
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

            // Nama file saja (path di disk private tidak dikirim). Isi file: GET /sertifikat/{id}/file.
            'file_sertifikat' => SertifikatService::namaFile($this->file_sertifikat),
            'jenis_file' => match (strtolower(pathinfo((string) $this->file_sertifikat, PATHINFO_EXTENSION))) {
                'pdf' => 'pdf',
                'jpg', 'jpeg', 'png' => 'gambar',
                default => null,
            },
            'url_sertifikat' => $this->url_sertifikat,
        ];
    }
}
