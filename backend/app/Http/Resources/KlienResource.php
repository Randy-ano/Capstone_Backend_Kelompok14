<?php

namespace App\Http\Resources;

use App\Models\Klien;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Klien
 */
class KlienResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_klien' => $this->id_klien,
            'nama_perusahaan' => $this->nama_perusahaan,
            'alamat' => $this->alamat,
            'no_telepon' => $this->no_telepon,
            'penanggung_jawab' => $this->penanggung_jawab,
        ];
    }
}
