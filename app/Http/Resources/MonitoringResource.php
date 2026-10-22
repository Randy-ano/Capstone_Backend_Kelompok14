<?php

namespace App\Http\Resources;

use App\Models\MonitoringTalent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MonitoringTalent
 */
class MonitoringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_monitoring' => $this->id_monitoring,
            'tanggal_monitoring' => $this->tanggal_monitoring?->toIso8601String(),
            'hasil_monitoring' => $this->hasil_monitoring,
            'catatan_tindak_lanjut' => $this->catatan_tindak_lanjut,
            'manager' => $this->manager ? ['id_pegawai' => $this->manager->id_pegawai, 'nama_pegawai' => $this->manager->nama_pegawai] : null,
            'placement' => PlacementResource::ringkas($this->placement),
        ];
    }
}
