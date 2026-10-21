<?php

namespace App\Http\Resources;

use App\Models\PengalamanKerjaTalent;
use App\Models\ScreeningTalent;
use App\Models\TalentPool;
use App\Services\PengalamanKerjaService;
use Illuminate\Http\Request;

/**
 * Detail talent pool: data daftar + profil, riwayat pengalaman, dan riwayat screening.
 *
 * @mixin TalentPool
 */
class TalentPoolDetailResource extends TalentPoolResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $talent = $this->talent;

        $data['talent'] += [
            'tanggal_lahir' => $talent->tanggal_lahir?->format('Y-m-d'),
            'nomor_telepon' => $talent->nomor_telepon,
            'tentang_saya' => $talent->tentang_saya,
            'gaji_harapan' => $talent->gaji_harapan,
            'linkedin' => $talent->linkedin,
            'github' => $talent->github,
            'portfolio' => $talent->portfolio,
            'riwayat_pendidikan' => RiwayatPendidikanResource::collection($talent->riwayatPendidikan),
            'riwayat_pengalaman_kerja' => $talent->pengalamanKerjaTalent
                ->sortByDesc('tanggal_mulai')
                ->values()
                ->map(fn (PengalamanKerjaTalent $p) => [
                    'nama_perusahaan' => $p->nama_perusahaan,
                    'posisi' => $p->posisi,
                    'tanggal_mulai' => $p->tanggal_mulai?->format('Y-m-d'),
                    'tanggal_selesai' => $p->tanggal_selesai?->format('Y-m-d'),
                    'masih_bekerja' => $p->masih_bekerja,
                ]),
            'lama_pengalaman_tahun' => round(app(PengalamanKerjaService::class)->lamaTahun($talent->pengalamanKerjaTalent), 1),
            'sertifikat' => SertifikatTalentResource::collection($talent->sertifikat),
        ];

        $data['riwayat_screening'] = $talent->screenings->map(fn (ScreeningTalent $s) => [
            'id_screening' => $s->id_screening,
            'id_job_req' => $s->id_job_req,
            'posisi' => $s->jobRequirement?->posisi,
            'nama_perusahaan' => $s->jobRequirement?->klien?->nama_perusahaan,
            'skor_kecocokan' => $s->skor_kecocokan,
            'tanggal_screening' => $s->tanggal_screening?->toIso8601String(),
            'hasil_screening' => $s->hasil_screening,
        ]);

        return $data;
    }
}
