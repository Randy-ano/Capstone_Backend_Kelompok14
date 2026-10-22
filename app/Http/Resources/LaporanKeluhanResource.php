<?php

namespace App\Http\Resources;

use App\Models\LaporanKeluhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaporanKeluhan
 */
class LaporanKeluhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_laporan' => $this->id_laporan,
            'judul_laporan' => $this->judul_laporan,
            'deskripsi_keluhan' => $this->deskripsi_keluhan,
            'tanggal_laporan' => $this->tanggal_laporan?->toIso8601String(),
            'status_laporan' => $this->status_laporan,
            'pelapor' => $this->hrClient ? ['id_hr_client' => $this->hrClient->id_hr_client, 'nama_hr' => $this->hrClient->nama_hr] : null,
            'tindak_lanjut' => [
                'oleh' => $this->penindakLanjut?->nama_pegawai,
                'hasil' => $this->hasil_tindak_lanjut,
                'tanggal' => $this->tanggal_tindak_lanjut?->toIso8601String(),
            ],
            'placement' => PlacementResource::ringkas($this->placement),
        ];
    }
}
