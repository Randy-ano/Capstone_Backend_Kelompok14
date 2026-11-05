<?php

namespace App\Http\Resources;

use App\Models\ScreeningTalent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScreeningTalent
 */
class ScreeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $job = $this->jobRequirement;

        return [
            'id_screening' => $this->id_screening,
            'skor_kecocokan' => $this->skor_kecocokan === null ? null : (float) $this->skor_kecocokan,
            'tanggal_screening' => $this->tanggal_screening?->toIso8601String(),
            // Catatan TS untuk AM; alasan AM dipisah ke `alasan_tidak_diteruskan`.
            'catatan_screening' => $this->catatanTs(),
            'alasan_tidak_diteruskan' => $this->alasanTidakDiteruskan(),
            'hasil_screening' => $this->hasil_screening,
            'status_usulan' => $this->when($this->relationLoaded('pengajuan'), fn () => $this->statusUsulan()),
            'label_status_usulan' => $this->when($this->relationLoaded('pengajuan'), fn () => ScreeningTalent::STATUS_USULAN[$this->statusUsulan()]),
            'id_pengajuan' => $this->whenLoaded('pengajuan', fn () => $this->pengajuan?->id_pengajuan),
            'talent' => [
                'id_talent' => $this->talent->id_talent,
                'nama_lengkap' => $this->talent->nama_lengkap,
                'email' => $this->talent->user?->email,
                'status_kandidat' => $this->talent->status_kandidat,
            ],
            'job_requirement' => [
                'id_job_req' => $job->id_job_req,
                'posisi' => $job->posisi,
                'status_req' => $job->status_req,
                'klien' => [
                    'id_klien' => $job->klien->id_klien,
                    'nama_perusahaan' => $job->klien->nama_perusahaan,
                ],
            ],
            'talent_support' => $this->talentSupport ? [
                'id_pegawai' => $this->talentSupport->id_pegawai,
                'nama_pegawai' => $this->talentSupport->nama_pegawai,
            ] : null,
        ];
    }
}
