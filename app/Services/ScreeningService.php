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
 * FR-08 (revisi 2026-11-05): usulan kandidat oleh Talent Support ke Account Manager (R10), ditinjau AM:
 * diteruskan ke klien (PengajuanService, R14) atau tidak diteruskan (R11). Log progres dicatat ScreeningTalentObserver (R12).
 */
class ScreeningService
{
    public const RELASI = ['talent.user', 'jobRequirement.klien', 'talentSupport', 'pengajuan'];

    public function __construct(
        private readonly RekomendasiService $rekomendasi,
        private readonly PemberitahuanService $pemberitahuan,
    ) {}

    /**
     * @param  array{id_job_req?: int|null, id_talent?: int|null, hasil?: string|null, status_usulan?: string|null, belum_diajukan?: bool|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return ScreeningTalent::query()
            ->with(self::RELASI)
            ->when($filter['id_job_req'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_job_req', $id))
            ->when($filter['id_talent'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_talent', $id))
            ->when($filter['hasil'] ?? null, fn (Builder $q, string $hasil) => $q->where('hasil_screening', $hasil))
            ->when($filter['status_usulan'] ?? null, fn (Builder $q, string $status) => $q->statusUsulan($status))
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
     * R10: usulan TS (hasil langsung `lolos`). Skor dari R9 disimpan sebagai informasi, tidak ada batas minimum.
     * Talent → dalam_proses; job diajukan → diproses; AM penanggung jawab dinotifikasi.
     *
     * @param  array{id_job_req: int, id_talent: int, catatan_screening?: string|null}  $data
     */
    public function buat(User $user, array $data): ScreeningTalent
    {
        $job = JobRequirement::with('kompetensi')->findOrFail($data['id_job_req']);
        $talent = Talent::with(['kompetensi', 'pengalamanKerjaTalent', 'talentPool'])->findOrFail($data['id_talent']);

        $this->rekomendasi->pastikanTerbuka($job);

        if ($job->screenings()->where('id_talent', $talent->id_talent)->exists()) {
            throw ValidationException::withMessages(['id_talent' => 'Talent ini sudah pernah diusulkan untuk permintaan kebutuhan ini.']);
        }

        if (
            $talent->talentPool?->status_ketersediaan !== StatusKetersediaan::Tersedia ||
            $talent->status_kandidat !== StatusKandidat::Tersedia
        ) {
            throw ValidationException::withMessages(['id_talent' => 'Talent tidak tersedia untuk diusulkan (belum masuk talent pool, tidak tersedia, atau sedang dalam proses lain).']);
        }

        $screening = DB::transaction(function () use ($user, $data, $job, $talent) {
            $screening = $job->screenings()->create([
                'id_talent' => $talent->id_talent,
                'id_ts' => $user->pegawai->id_pegawai,
                'skor_kecocokan' => $this->rekomendasi->skor($job, $talent)['skor_kecocokan'],
                'tanggal_screening' => now(),
                'catatan_screening' => $data['catatan_screening'] ?? null,
                'hasil_screening' => HasilScreening::Lolos,
            ]);

            $talent->update(['status_kandidat' => StatusKandidat::DalamProses]);
            if ($job->status_req === StatusReq::Diajukan) {
                $job->update(['status_req' => StatusReq::Diproses]);
            }

            return $screening;
        });
        $this->pemberitahuan->usulanDibuat($screening);

        return $screening->load(self::RELASI);
    }

    /**
     * R11 (revisi): AM tidak meneruskan usulan ke klien. Hasil → tidak_lolos (final), alasan disimpan setelah catatan TS,
     * talent kembali tersedia (bisa diusulkan untuk permintaan lain), TS pengusul dinotifikasi.
     */
    public function tidakDiteruskan(ScreeningTalent $screening, string $alasan): ScreeningTalent
    {
        $this->pastikanBisaDitinjau($screening, 'alasan');

        $screening = DB::transaction(function () use ($screening, $alasan) {
            $screening->update([
                'hasil_screening' => HasilScreening::TidakLolos,
                'catatan_screening' => ScreeningTalent::gabungCatatan($screening->catatanTs(), $alasan),
            ]);
            $screening->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);

            return $screening;
        });
        $this->pemberitahuan->usulanTidakDiteruskan($screening);

        return $screening->load(self::RELASI);
    }

    /**
     * Usulan masih bisa ditinjau/diubah: job belum selesai, belum diteruskan ke klien.
     */
    private function pastikanBisaDitinjau(ScreeningTalent $screening, string $field): void
    {
        $job = $screening->jobRequirement;
        if (in_array($job->status_req, ProgresService::STATUS_SELESAI, true)) {
            throw ValidationException::withMessages([$field => "Permintaan kebutuhan sudah {$job->status_req->value}; usulan tidak dapat diubah."]);
        }
        if ($screening->pengajuan()->exists()) {
            throw ValidationException::withMessages([$field => 'Usulan ini sudah diteruskan ke klien.']);
        }
        if ($screening->hasil_screening === HasilScreening::TidakLolos) {
            throw ValidationException::withMessages([$field => 'Usulan ini sudah tidak diteruskan.']);
        }
    }

    /**
     * Ubah hasil/catatan (endpoint lama, dipertahankan). R11: tidak_lolos → talent kembali tersedia dan final.
     * Alasan AM (jika ada) tetap tersimpan saat catatan TS diubah.
     *
     * @param  array{hasil_screening?: string, catatan_screening?: string|null}  $data
     */
    public function ubah(ScreeningTalent $screening, array $data): ScreeningTalent
    {
        $job = $screening->jobRequirement;
        if (in_array($job->status_req, ProgresService::STATUS_SELESAI, true)) {
            throw ValidationException::withMessages(['hasil_screening' => "Permintaan kebutuhan sudah {$job->status_req->value}; screening tidak dapat diubah."]);
        }
        if ($screening->pengajuan()->exists()) {
            throw ValidationException::withMessages(['hasil_screening' => 'Screening yang sudah diajukan ke klien tidak dapat diubah.']);
        }

        $hasilBaru = isset($data['hasil_screening']) ? HasilScreening::from($data['hasil_screening']) : $screening->hasil_screening;
        if ($screening->hasil_screening === HasilScreening::TidakLolos && $hasilBaru !== HasilScreening::TidakLolos) {
            throw ValidationException::withMessages(['hasil_screening' => 'Hasil screening tidak lolos tidak dapat diubah lagi.']);
        }

        return DB::transaction(function () use ($screening, $data) {
            $ubah = Arr::only($data, ['hasil_screening', 'catatan_screening']);
            if (array_key_exists('catatan_screening', $ubah)) {
                $ubah['catatan_screening'] = ScreeningTalent::gabungCatatan($ubah['catatan_screening'], $screening->alasanTidakDiteruskan());
            }
            $screening->update($ubah);

            if ($screening->wasChanged('hasil_screening') && $screening->hasil_screening === HasilScreening::TidakLolos) {
                $screening->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);
            }

            return $screening->load(self::RELASI);
        });
    }
}
