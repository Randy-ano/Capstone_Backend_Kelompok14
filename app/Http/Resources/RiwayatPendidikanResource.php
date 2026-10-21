<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatPendidikanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_riwayat_pendidikan' => $this->id_riwayat_pendidikan,
            'jenjang' => $this->jenjang,
            'nama_institusi' => $this->nama_institusi,
            'jurusan' => $this->jurusan,
            'tahun_mulai' => $this->tahun_mulai,
            'tahun_selesai' => $this->tahun_selesai,
            'deskripsi' => $this->deskripsi,
        ];
    }
}
