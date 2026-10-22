<?php

namespace App\Http\Resources;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notifikasi
 */
class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_notifikasi' => $this->id_notifikasi,
            'tipe_notifikasi' => $this->tipe_notifikasi,
            'pesan' => $this->pesan,
            'kanal' => $this->kanal,
            'status_pengiriman' => $this->status_pengiriman,
            'status_baca' => $this->status_baca,
            'dibuat_pada' => ($this->jadwal_kirim ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
