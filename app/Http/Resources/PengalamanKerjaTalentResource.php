<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengalamanKerjaTalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pengalaman_kerja' => $this->id_pengalaman_kerja,
            'nama_perusahaan' => $this->nama_perusahaan,
            'posisi' => $this->posisi,
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'lokasi' => $this->lokasi,

            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),

            'masih_bekerja' => $this->masih_bekerja,
            'deskripsi' => $this->deskripsi,
        ];
    }
}
