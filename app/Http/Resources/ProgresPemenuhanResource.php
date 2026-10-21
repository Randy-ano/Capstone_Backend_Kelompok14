<?php

namespace App\Http\Resources;

use App\Models\ProgresPemenuhan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris log progres pemenuhan.
 *
 * @mixin ProgresPemenuhan
 */
class ProgresPemenuhanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_progres' => $this->id_progres,
            'status_tahapan' => $this->status_tahapan,
            'indikator_sla' => $this->indikator_sla,
            'log_waktu_pembaruan' => $this->log_waktu_pembaruan?->toIso8601String(),
            'talent' => $this->talent ? [
                'id_talent' => $this->talent->id_talent,
                'nama_lengkap' => $this->talent->nama_lengkap,
            ] : null,
            'penanggung_jawab' => $this->whenLoaded('penanggungJawab', fn () => $this->penanggungJawab ? [
                'id_pegawai' => $this->penanggungJawab->id_pegawai,
                'nama_pegawai' => $this->penanggungJawab->nama_pegawai,
            ] : null),
        ];
    }
}
