<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentCvResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_talent' => $this->id_talent,
            'nama_lengkap' => $this->nama_lengkap,
            'file_cv' => $this->file_cv,
            'ada_cv' => $this->file_cv !== null,
        ];
    }
}
