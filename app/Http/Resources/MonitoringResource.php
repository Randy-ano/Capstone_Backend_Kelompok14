<?php

namespace App\Http\Resources;

use App\Models\MonitoringTalent;
use App\Services\DokumenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MonitoringTalent
 */
class MonitoringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nomorSp = $this->nomorSp();
        $pencatat = $this->pencatat;

        return [
            'id_monitoring' => $this->id_monitoring,
            'tipe' => $this->tipe,
            'label_tipe' => $nomorSp ? "SP {$nomorSp}" : $this->tipe->label(),
            'nomor_sp' => $nomorSp,
            'tanggal_monitoring' => $this->tanggal_monitoring?->toIso8601String(),
            'hasil_monitoring' => $this->hasil_monitoring,
            'catatan_tindak_lanjut' => $this->catatan_tindak_lanjut,
            // Status tampil penempatan yang dimonitor (sama dengan placement.status_tampil).
            'status_tampil' => $this->placement->statusTampil(),
            'label_status_tampil' => $this->placement->statusTampil()->label(),
            'pencatat' => $pencatat ? [
                'id_user' => $pencatat->id_user,
                'nama' => $pencatat->namaTampilan(),
                'role' => $pencatat->role,
                'label_role' => $pencatat->role->label(),
            ] : null,
            'dokumen' => $this->dokumen ? [
                'id_dokumen' => $this->dokumen->id_dokumen,
                'nama_file' => DokumenService::namaFile($this->dokumen),
                'status_dokumen' => $this->dokumen->status_dokumen,
            ] : null,
            'placement' => PlacementResource::ringkas($this->placement),
        ];
    }
}
