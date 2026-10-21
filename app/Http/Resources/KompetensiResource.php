<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KompetensiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_kompetensi' => $this->id_kompetensi,
            'nama_kompetensi' => $this->nama_kompetensi,
            'kategori' => $this->kategori,
        ];
    }
}
