<?php

namespace App\Http\Resources;

use App\Models\PengajuanCuti;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PengajuanCuti
 */
class CutiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_cuti' => $this->id_cuti,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'jumlah_hari' => $this->tanggal_mulai && $this->tanggal_selesai ? (int) $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1 : null,
            'alasan_cuti' => $this->alasan_cuti,
            'status_cuti' => $this->status_cuti,
            'approval_klien' => [
                'status' => $this->status_approval_klien,
                'oleh' => $this->approverKlien?->nama_hr,
            ],
            'approval_manager' => [
                'status' => $this->status_approval_manager,
                'oleh' => $this->approverManager?->nama_pegawai,
            ],
            'diajukan_pada' => $this->created_at?->toIso8601String(),
            'placement' => PlacementResource::ringkas($this->placement),
        ];
    }
}
