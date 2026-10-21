<?php

namespace App\Services;

use App\Enums\HasilScreening;
use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-08: screening kandidat oleh Talent Support (R10, R11). Log progres dicatat ScreeningTalentObserver (R12).
 */
class ScreeningService
{
    public const RELASI = ['talent.user', 'jobRequirement.klien', 'talentSupport', 'pengajuan'];

    public function __construct(private readonly RekomendasiService $rekomendasi) {}

    /**
     * @param  array{id_job_req?: int|null, id_talent?: int|null, hasil?: string|null, belum_diajukan?: bool|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return ScreeningTalent::query()
            ->with(self::RELASI)
            ->when($filter['id_job_req'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_job_req', $id))
            ->when($filter['id_talent'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_talent', $id))
            ->when($filter['hasil'] ?? null, fn (Builder $q, string $hasil) => $q->where('hasil_screening', $hasil))
            ->when($filter['belum_diajukan'] ?? false, fn (Builder $q) => $q->doesntHave('pengajuan'))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->whereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))
                ->orWhereHas('jobRequirement', fn (Builder $q) => $q->where('posisi', 'like', "%{$search}%"))))
            ->orderByDesc('tanggal_screening')
            ->orderByDesc('id_screening')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * R10: skor dari R9; talent → dalam_proses; job diajukan → diproses.
     *
     * @param  array{id_job_req: int, id_talent: int, catatan_screening?: string|null}  $data
     */
    public function buat(User $user, array $data): ScreeningTalent
    {
        $job = JobRequirement::with('kompetensi')->findOrFail($data['id_job_req']);
        $talent = Talent::with(['kompetensi', 'pengalamanKerjaTalent', 'talentPool'])->findOrFail($data['id_talent']);

        $this->rekomendasi->pastikanTerbuka($job);

        if ($job->screenings()->where('id_talent', $talent->id_talent)->exists()) {
            throw ValidationException::withMessages(['id_talent' => 'Talent sudah pernah di-screening untuk job requirement ini.']);
        }

        if (
            $talent->talentPool?->status_ketersediaan !== StatusKetersediaan::Tersedia ||
            $talent->status_kandidat !== StatusKandidat::Tersedia
        ) {
            throw ValidationException::withMessages(['id_talent' => 'Talent tidak tersedia untuk di-screening (belum masuk talent pool, tidak tersedia, atau sedang dalam proses lain).']);
        }

        return DB::transaction(function () use ($user, $data, $job, $talent) {
            $screening = $job->screenings()->create([
                'id_talent' => $talent->id_talent,
                'id_ts' => $user->pegawai->id_pegawai,
                'skor_kecocokan' => $this->rekomendasi->skor($job, $talent)['skor_kecocokan'],
                'tanggal_screening' => now(),
                'catatan_screening' => $data['catatan_screening'] ?? null,
            ]);

            $talent->update(['status_kandidat' => StatusKandidat::DalamProses]);
            if ($job->status_req === StatusReq::Diajukan) {
                $job->update(['status_req' => StatusReq::Diproses]);
            }

            return $screening->load(self::RELASI);
        });
    }

    /**
     * Ubah hasil/catatan. R11: tidak_lolos → talent kembali tersedia. Hasil tidak_lolos bersifat final.
     *
     * @param  array{hasil_screening?: string, catatan_screening?: string|null}  $data
     */
    public function ubah(ScreeningTalent $screening, array $data): ScreeningTalent
    {
        $job = $screening->jobRequirement;
        if (in_array($job->status_req, ProgresService::STATUS_SELESAI, true)) {
            throw ValidationException::withMessages(['hasil_screening' => "Job requirement sudah {$job->status_req->value}; screening tidak dapat diubah."]);
        }
        if ($screening->pengajuan()->exists()) {
            throw ValidationException::withMessages(['hasil_screening' => 'Screening yang sudah diajukan ke klien tidak dapat diubah.']);
        }

        $hasilBaru = isset($data['hasil_screening']) ? HasilScreening::from($data['hasil_screening']) : $screening->hasil_screening;
        if ($screening->hasil_screening === HasilScreening::TidakLolos && $hasilBaru !== HasilScreening::TidakLolos) {
            throw ValidationException::withMessages(['hasil_screening' => 'Hasil screening tidak lolos tidak dapat diubah lagi.']);
        }

        return DB::transaction(function () use ($screening, $data) {
            $screening->update(Arr::only($data, ['hasil_screening', 'catatan_screening']));

            if ($screening->wasChanged('hasil_screening') && $screening->hasil_screening === HasilScreening::TidakLolos) {
                $screening->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);
            }

            return $screening->load(self::RELASI);
        });
    }
}
