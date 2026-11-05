<?php

namespace App\Http\Resources;

use App\Enums\StatusTampilPlacement;
use App\Models\Placement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kartu monitoring per talent (satu penempatan): status kontrak, periode, sisa hari, jumlah catatan & SP.
 *
 * @mixin Placement
 */
class MonitoringTalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->statusTampil();
        $hariIni = now()->startOfDay();

        return [
            ...PlacementResource::ringkas($this->resource),
            'email' => $this->talent->user?->email,
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            // Hari tersisa sampai kontrak berakhir (Aktif/Belum Aktif dengan tanggal selesai), hari menuju mulai (Belum Aktif).
            'sisa_hari' => $status !== StatusTampilPlacement::Selesai && $this->tanggal_selesai
                ? (int) $hariIni->diffInDays($this->tanggal_selesai->copy()->startOfDay())
                : null,
            'hari_menuju_mulai' => $status === StatusTampilPlacement::BelumAktif
                ? (int) $hariIni->diffInDays($this->tanggal_mulai->copy()->startOfDay())
                : null,
            'jumlah_catatan' => (int) $this->jumlah_catatan,
            'jumlah_sp' => (int) $this->jumlah_sp,
        ];
    }
}
