<?php

namespace App\Http\Resources;

use App\Models\HrClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HrClient
 */
class HrClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_hr_client' => $this->id_hr_client,
            'nama_hr' => $this->nama_hr,
            'jabatan' => $this->jabatan,
            'klien' => new KlienResource($this->whenLoaded('klien')),
        ];
    }
}
