<?php

namespace App\Http\Resources;

use App\Models\Placement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Placement; detail menambah ringkasan cuti, shift, monitoring, dan keluhan.
 *
 * @mixin Placement
 */
class PlacementResource extends JsonResource
{
    /** Relasi yang dibutuhkan ringkas(). */
    public const RELASI_RINGKAS = ['placement.talent', 'placement.klien'];

    /**
     * Ringkasan placement untuk data operasional (cuti, shift, monitoring, keluhan).
     *
     * @return array<string, mixed>
     */
    public static function ringkas(Placement $placement): array
    {
        return [
            'id_placement' => $placement->id_placement,
            'posisi_penempatan' => $placement->posisi_penempatan,
            'status_placement' => $placement->status_placement,
            'talent' => ['id_talent' => $placement->talent->id_talent, 'nama_lengkap' => $placement->talent->nama_lengkap],
            'klien' => ['id_klien' => $placement->klien->id_klien, 'nama_perusahaan' => $placement->klien->nama_perusahaan],
        ];
    }

    public function toArray(Request $request): array
    {
        $atribut = $this->resource->getAttributes();

        return [
            'id_placement' => $this->id_placement,
            'id_evaluasi' => $this->id_evaluasi,
            'posisi_penempatan' => $this->posisi_penempatan,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'status_placement' => $this->status_placement,
            'talent' => [
                'id_talent' => $this->talent->id_talent,
                'nama_lengkap' => $this->talent->nama_lengkap,
                'email' => $this->talent->user?->email,
            ],
            'klien' => ['id_klien' => $this->klien->id_klien, 'nama_perusahaan' => $this->klien->nama_perusahaan],
            'job_requirement' => ['id_job_req' => $this->jobRequirement->id_job_req, 'posisi' => $this->jobRequirement->posisi],
            'ringkasan' => $this->when(array_key_exists('cuti_count', $atribut), fn () => [
                'jumlah_cuti' => (int) $atribut['cuti_count'],
                'jumlah_shift' => (int) $atribut['shifts_count'],
                'jumlah_monitoring' => (int) $atribut['monitorings_count'],
                'jumlah_laporan_keluhan' => (int) $atribut['laporan_keluhan_count'],
            ]),
        ];
    }
}
