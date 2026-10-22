<?php

namespace App\Http\Resources;

use App\Models\ShiftingTalent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShiftingTalent
 */
class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pengelola = $this->pengelola;

        return [
            'id_shift' => $this->id_shift,
            'tanggal_shift' => $this->tanggal_shift?->format('Y-m-d'),
            'jenis_shift' => $this->jenis_shift,
            'keterangan' => $this->keterangan,
            // Nama HR Client atau Manager yang terakhir mengatur shift ini.
            'diatur_oleh' => $pengelola ? ($pengelola->hrClient?->nama_hr ?? $pengelola->pegawai?->nama_pegawai) : null,
            'placement' => PlacementResource::ringkas($this->placement),
        ];
    }
}
