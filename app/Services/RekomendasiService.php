<?php

namespace App\Services;

use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * FR-07, R9: rekomendasi kandidat berbasis aturan — mencocokkan kriteria job requirement
 * dengan data profil talent (kompetensi & pengalaman kerja). Tidak membaca file PDF CV.
 */
class RekomendasiService
{
    private const BOBOT_KOMPETENSI = 0.8;

    private const BOBOT_PENGALAMAN = 0.2;

    /** Status job yang boleh dicarikan kandidat. */
    public const STATUS_TERBUKA = [StatusReq::Diajukan, StatusReq::Diproses];

    public function __construct(private readonly PengalamanKerjaService $pengalaman) {}

    /**
     * Kandidat untuk job: talent di pool berstatus tersedia, belum pernah di-screening untuk job ini,
     * dan cocok minimal 1 kompetensi. Urut skor tertinggi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function daftar(JobRequirement $job): Collection
    {
        $this->pastikanTerbuka($job);
        $job->loadMissing('kompetensi');

        return Talent::query()
            ->with(['kompetensi', 'pengalamanKerjaTalent', 'talentPool', 'riwayatPendidikan'])
            ->where('status_kandidat', StatusKandidat::Tersedia)
            ->whereHas('talentPool', fn (Builder $q) => $q->where('status_ketersediaan', StatusKetersediaan::Tersedia))
            ->whereDoesntHave('screenings', fn (Builder $q) => $q->where('id_job_req', $job->id_job_req))
            ->whereHas('kompetensi', fn (Builder $q) => $q->whereIn('kompetensi.id_kompetensi', $job->kompetensi->modelKeys()))
            ->get()
            ->map(fn (Talent $talent) => ['talent' => $talent, ...$this->skor($job, $talent)])
            ->filter(fn (array $kandidat) => $kandidat['skor_kompetensi'] > 0)
            ->sortBy([['skor_kecocokan', 'desc'], ['lama_pengalaman', 'desc'], [fn ($a, $b) => strcmp($a['talent']->nama_lengkap, $b['talent']->nama_lengkap)]])
            ->values();
    }

    /**
     * R9: skor_kecocokan = 0,8 × skor_kompetensi + 0,2 × skor_pengalaman (2 desimal).
     *
     * @return array{skor_kecocokan: float, skor_kompetensi: float, skor_pengalaman: float, lama_pengalaman: float, kompetensi_cocok: list<string>, kompetensi_kurang: list<string>}
     */
    public function skor(JobRequirement $job, Talent $talent): array
    {
        $dibutuhkan = $job->kompetensi;
        $dimiliki = $talent->kompetensi->modelKeys();
        [$cocok, $kurang] = $dibutuhkan->partition(fn ($k) => in_array($k->id_kompetensi, $dimiliki, true));

        $skorKompetensi = $dibutuhkan->isEmpty() ? 0.0 : $cocok->count() / $dibutuhkan->count() * 100;

        // Dibulatkan 1 desimal dulu agar skor sesuai dengan lama pengalaman yang ditampilkan (Jan–Des = 1,0 tahun).
        $lama = round($this->pengalaman->lamaTahun($talent->pengalamanKerjaTalent), 1);
        $skorPengalaman = $job->pengalaman_min <= 0 || $lama >= $job->pengalaman_min
            ? 100.0
            : $lama / $job->pengalaman_min * 100;

        return [
            'skor_kecocokan' => round(self::BOBOT_KOMPETENSI * $skorKompetensi + self::BOBOT_PENGALAMAN * $skorPengalaman, 2),
            'skor_kompetensi' => round($skorKompetensi, 2),
            'skor_pengalaman' => round($skorPengalaman, 2),
            'lama_pengalaman' => $lama,
            'kompetensi_cocok' => $cocok->pluck('nama_kompetensi')->values()->all(),
            'kompetensi_kurang' => $kurang->pluck('nama_kompetensi')->values()->all(),
        ];
    }

    public function pastikanTerbuka(JobRequirement $job, string $field = 'id_job_req'): void
    {
        if (! in_array($job->status_req, self::STATUS_TERBUKA, true)) {
            throw ValidationException::withMessages([
                $field => 'Kandidat hanya dapat dicari untuk job requirement berstatus diajukan atau diproses.',
            ]);
        }
    }
}
