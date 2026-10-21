<?php

namespace App\Http\Resources;

use App\Models\JadwalInterview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jadwal interview; ringkasan pengajuan ikut jika relasinya dimuat (daftar interview).
 *
 * @mixin JadwalInterview
 */
class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_interview' => $this->id_interview,
            'tanggal_interview' => $this->tanggal_interview?->format('Y-m-d'),
            'waktu_interview' => $this->waktu_interview ? substr($this->waktu_interview, 0, 5) : null,
            'link_lokasi_meeting' => $this->link_lokasi_meeting,
            'instruksi' => $this->instruksi,
            'status_interview' => $this->status_interview,
            'pengajuan' => $this->whenLoaded('pengajuan', fn () => [
                'id_pengajuan' => $this->pengajuan->id_pengajuan,
                'status_pengajuan' => $this->pengajuan->status_pengajuan,
                'talent' => ['id_talent' => $this->pengajuan->talent->id_talent, 'nama_lengkap' => $this->pengajuan->talent->nama_lengkap],
                'job_requirement' => [
                    'id_job_req' => $this->pengajuan->jobRequirement->id_job_req,
                    'posisi' => $this->pengajuan->jobRequirement->posisi,
                    'klien' => [
                        'id_klien' => $this->pengajuan->jobRequirement->klien->id_klien,
                        'nama_perusahaan' => $this->pengajuan->jobRequirement->klien->nama_perusahaan,
                    ],
                ],
            ]),
        ];
    }
}
