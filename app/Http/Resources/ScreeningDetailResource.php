<?php

namespace App\Http\Resources;

use App\Models\Bahasa;
use App\Models\ScreeningTalent;
use App\Services\RekomendasiService;
use Illuminate\Http\Request;

/**
 * Detail usulan kandidat (GET /screening/{id}) untuk tinjauan Account Manager sebelum meneruskan ke klien.
 * Data daftar + profil kandidat + rincian kecocokan R9 yang dihitung ulang dari profil saat ini.
 * Kompetensi tidak punya level (hanya kategori); tingkat kemampuan hanya ada pada bahasa.
 *
 * @mixin ScreeningTalent
 */
class ScreeningDetailResource extends ScreeningResource
{
    /**
     * @param  array{skor_kecocokan: float, skor_kompetensi: float, skor_pengalaman: float, lama_pengalaman: float, kompetensi_cocok: list<string>, kompetensi_kurang: list<string>}  $rincian
     */
    public function __construct(ScreeningTalent $resource, private readonly array $rincian)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $talent = $this->talent;
        $job = $this->jobRequirement;

        $data['talent'] += [
            'id_talent_pool' => $talent->talentPool?->id_talent_pool,
            'ada_cv' => $talent->file_cv !== null,
            'domisili' => $talent->domisili,
            'pendidikan' => $talent->ringkasanPendidikan(),
            'riwayat_pendidikan' => RiwayatPendidikanResource::collection(
                $talent->riwayatPendidikan->sortByDesc(fn ($r) => [$r->tahun_selesai ?? PHP_INT_MAX, $r->tahun_mulai ?? 0])->values()
            ),
            'riwayat_pengalaman_kerja' => PengalamanKerjaTalentResource::collection(
                $talent->pengalamanKerjaTalent->sortByDesc('tanggal_mulai')->values()
            ),
            'lama_pengalaman_tahun' => $this->rincian['lama_pengalaman'],
            'kompetensi' => KompetensiResource::collection($talent->kompetensi->sortBy('nama_kompetensi')->values()),
            'bahasa' => $talent->bahasa->map(fn (Bahasa $b) => [
                'id_bahasa' => $b->id_bahasa,
                'nama_bahasa' => $b->nama_bahasa,
                'tingkat_kemampuan' => $b->pivot->tingkat_kemampuan,
            ])->values(),
            'sertifikat' => SertifikatTalentResource::collection($talent->sertifikat),
        ];

        $data['job_requirement'] += [
            'pengalaman_min' => $job->pengalaman_min,
            'jumlah_kompetensi' => $job->kompetensi->count(),
        ];

        // Dihitung ulang dari profil saat ini; bisa berbeda dari `skor_kecocokan` tersimpan bila profil/kebutuhan berubah.
        $data['rincian_kecocokan'] = [
            'skor_kecocokan' => $this->rincian['skor_kecocokan'],
            'skor_kompetensi' => $this->rincian['skor_kompetensi'],
            'skor_pengalaman' => $this->rincian['skor_pengalaman'],
            'bobot_kompetensi' => (int) round(RekomendasiService::BOBOT_KOMPETENSI * 100),
            'bobot_pengalaman' => (int) round(RekomendasiService::BOBOT_PENGALAMAN * 100),
            'kompetensi_cocok' => $this->rincian['kompetensi_cocok'],
            'kompetensi_kurang' => $this->rincian['kompetensi_kurang'],
            'lama_pengalaman' => $this->rincian['lama_pengalaman'],
            'pengalaman_min' => $job->pengalaman_min,
        ];

        return $data;
    }
}
