<?php

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\PengajuanKandidat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengajuan kandidat. Talent tidak melihat catatan screening maupun nilai/catatan evaluasi (hanya hasilnya).
 *
 * @mixin PengajuanKandidat
 */
class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $talent = $this->talent;
        $job = $this->jobRequirement;
        $olehTalent = $request->user()?->hasRole(Role::Talent);

        return [
            'id_pengajuan' => $this->id_pengajuan,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->toIso8601String(),
            'status_pengajuan' => $this->status_pengajuan,
            'talent' => [
                'id_talent' => $talent->id_talent,
                'nama_lengkap' => $talent->nama_lengkap,
                'email' => $talent->user?->email,
                'pendidikan' => $talent->ringkasanPendidikan(),
                'status_kandidat' => $talent->status_kandidat,
            ],
            'job_requirement' => [
                'id_job_req' => $job->id_job_req,
                'posisi' => $job->posisi,
                'status_req' => $job->status_req,
                'klien' => ['id_klien' => $job->klien->id_klien, 'nama_perusahaan' => $job->klien->nama_perusahaan],
            ],
            'account_manager' => $this->accountManager ? [
                'id_pegawai' => $this->accountManager->id_pegawai,
                'nama_pegawai' => $this->accountManager->nama_pegawai,
            ] : null,
            'screening' => $this->when(! $olehTalent && $this->relationLoaded('screening'), fn () => [
                'id_screening' => $this->screening->id_screening,
                'skor_kecocokan' => $this->screening->skor_kecocokan === null ? null : (float) $this->screening->skor_kecocokan,
                'catatan_screening' => $this->screening->catatan_screening,
            ]),
            'jumlah_interview' => $this->whenCounted('interviews'),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),
            'evaluasi' => $this->whenLoaded('evaluasi', fn () => $this->evaluasi === null ? null : ($olehTalent
                ? ['status_evaluasi' => $this->evaluasi->status_evaluasi]
                : new EvaluasiResource($this->evaluasi))),
            'id_placement' => $this->whenLoaded('evaluasi', fn () => $this->evaluasi?->placement?->id_placement),
        ];
    }
}
