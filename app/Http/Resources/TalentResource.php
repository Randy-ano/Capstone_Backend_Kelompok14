<?php

namespace App\Http\Resources;

use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Talent
 */
class TalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Identitas talent
            'id_talent' => $this->id_talent,
            'nama_lengkap' => $this->nama_lengkap,

            // Data pribadi
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'nomor_telepon' => $this->nomor_telepon,
            'alamat' => $this->alamat,
            'domisili' => $this->domisili,
            'tentang_saya' => $this->tentang_saya,

            // Data akademik utama
            'pendidikan' => $this->pendidikan,
            'nama_institusi' => $this->nama_institusi,
            'jurusan' => $this->jurusan,
            'tahun_lulus' => $this->tahun_lulus,

            // Riwayat pendidikan
            'riwayat_pendidikan' => RiwayatPendidikanResource::collection(
                $this->whenLoaded('riwayatPendidikan')
            ),

            // Pengalaman kerja
            'pengalaman_kerja' => $this->pengalaman_kerja,
            'riwayat_pengalaman_kerja' => PengalamanKerjaTalentResource::collection(
                $this->whenLoaded('pengalamanKerjaTalent')
            ),

            // Gaji harapan
            'gaji_harapan' => $this->gaji_harapan,

            // Media sosial dan portofolio
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'github' => $this->github,
            'portfolio' => $this->portfolio,

            // Kompetensi atau skills
            'kompetensi' => KompetensiResource::collection(
                $this->whenLoaded('kompetensi')
            ),

            // Sertifikat
            'sertifikat' => SertifikatTalentResource::collection(
                $this->whenLoaded('sertifikat')
            ),

            // Bahasa beserta tingkat kemampuan
            'bahasa' => $this->whenLoaded('bahasa', function () {
                return $this->bahasa->map(function ($bahasa) {
                    return [
                        'id_bahasa' => $bahasa->id_bahasa,
                        'nama_bahasa' => $bahasa->nama_bahasa,
                        'tingkat_kemampuan' => $bahasa->pivot->tingkat_kemampuan,
                    ];
                });
            }),

            // Status profil
            'status_kelengkapan' => $this->status_kelengkapan,
            // Checklist R4 untuk halaman Profil & CV (feedback SUS 2026-11-14).
            'kelengkapan_profil' => $this->when(
                $this->relationLoaded('riwayatPendidikan') && $this->relationLoaded('kompetensi'),
                fn () => $this->kelengkapanProfil(),
            ),
            'status_kandidat' => $this->status_kandidat,

            // Informasi CV
            'ada_cv' => $this->file_cv !== null,
        ];
    }

    /** @return array{syarat: list<array{kunci: string, label: string, terpenuhi: bool}>, terpenuhi: int, total: int, lengkap: bool, di_talent_pool: bool} */
    private function kelengkapanProfil(): array
    {
        $syarat = $this->syaratKelengkapan();
        $terpenuhi = count(array_filter($syarat, fn (array $s) => $s['terpenuhi']));

        return [
            'syarat' => $syarat,
            'terpenuhi' => $terpenuhi,
            'total' => count($syarat),
            'lengkap' => $terpenuhi === count($syarat),
            'di_talent_pool' => $this->talentPool()->exists(),
        ];
    }
}
