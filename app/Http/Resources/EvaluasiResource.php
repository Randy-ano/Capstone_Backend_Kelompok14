<?php

namespace App\Http\Resources;

use App\Models\EvaluasiKandidat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EvaluasiKandidat
 */
class EvaluasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_evaluasi' => $this->id_evaluasi,
            'id_pengajuan' => $this->id_pengajuan,
            'nilai_score' => $this->nilai_score,
            'catatan_evaluasi' => $this->catatan_evaluasi,
            'status_evaluasi' => $this->status_evaluasi,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'hr_client' => $this->whenLoaded('hrClient', fn () => $this->hrClient ? [
                'id_hr_client' => $this->hrClient->id_hr_client,
                'nama_hr' => $this->hrClient->nama_hr,
            ] : null),
            'id_placement' => $this->whenLoaded('placement', fn () => $this->placement?->id_placement),
            'pengajuan' => $this->whenLoaded('pengajuan', fn () => [
                'id_pengajuan' => $this->pengajuan->id_pengajuan,
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
