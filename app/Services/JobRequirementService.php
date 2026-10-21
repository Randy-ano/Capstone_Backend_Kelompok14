<?php

namespace App\Services;

use App\Enums\HasilScreening;
use App\Enums\IndikatorSla;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-05: job requirement dibuat HR Client (untuk kliennya) atau AM (memilih klien).
 * HR Client hanya melihat job milik kliennya; internal melihat semua.
 */
class JobRequirementService
{
    /** Relasi yang ditampilkan di daftar job requirement. */
    public const RELASI_DAFTAR = ['klien', 'accountManager', 'kompetensi'];

    /** Status yang masih boleh diubah isinya. */
    private const STATUS_BISA_DIUBAH = [StatusReq::Draft, StatusReq::Diajukan];

    /** Status yang masih boleh dibatalkan. */
    private const STATUS_BISA_DIBATALKAN = [StatusReq::Draft, StatusReq::Diajukan, StatusReq::Diproses];

    public function __construct(private readonly ProgresService $progres) {}

    /** withCount untuk jumlah placement aktif (kolom "terpenuhi"). */
    public static function hitungTerpenuhi(): array
    {
        return ['placements as terpenuhi' => fn (Builder $q) => $q->where('status_placement', StatusPlacement::Aktif)];
    }

    /**
     * @param  array{status?: string|null, indikator_sla?: string|null, id_klien?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        // HR Client selalu terkunci ke kliennya; filter klien hanya untuk internal.
        if ($user->hasRole(Role::HrClient)) {
            unset($filter['id_klien']);
        }

        $query = $this->milik($user)
            ->with(self::RELASI_DAFTAR)
            ->withCount(self::hitungTerpenuhi());

        return $this->terapkanFilter($query, $filter)
            ->orderByDesc('id_job_req')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Jumlah job per status dan per indikator SLA yang dapat dilihat user (tidak terpengaruh filter).
     *
     * @return array<string, int>
     */
    public function ringkasan(User $user): array
    {
        $perStatus = $this->milik($user)
            ->selectRaw('status_req, count(*) as jumlah')
            ->groupBy('status_req')
            ->pluck('jumlah', 'status_req');

        $ringkasan = ['total' => (int) $perStatus->sum()];
        foreach (StatusReq::cases() as $status) {
            $ringkasan[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }
        foreach ([IndikatorSla::Terlambat, IndikatorSla::Mendekati] as $indikator) {
            $ringkasan["sla_{$indikator->value}"] = $this->progres->filterIndikatorSla($this->milik($user), $indikator)->count();
        }

        return $ringkasan;
    }

    /**
     * Filter bersama untuk daftar job requirement dan tabel monitoring progres.
     */
    public function terapkanFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_req', $status))
            ->when($filter['id_klien'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_klien', $id))
            ->when($filter['indikator_sla'] ?? null, fn (Builder $q, string $indikator) => $this->progres->filterIndikatorSla($q, IndikatorSla::from($indikator)))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->where('posisi', 'like', "%{$search}%")
                ->orWhereHas('klien', fn (Builder $q) => $q->where('nama_perusahaan', 'like', "%{$search}%"))));
    }

    public function detail(JobRequirement $job): JobRequirement
    {
        return $job->load([...self::RELASI_DAFTAR, 'progresTerakhir.talent'])
            ->loadCount([
                ...self::hitungTerpenuhi(),
                'screenings',
                ...collect(HasilScreening::cases())->mapWithKeys(fn (HasilScreening $h) => [
                    "screenings as screening_{$h->value}" => fn (Builder $q) => $q->where('hasil_screening', $h),
                ])->all(),
            ]);
    }

    /**
     * R7: HR Client → klien miliknya; AM → klien pilihan dan menjadi AM penanggung jawab. Minimal 1 kompetensi.
     *
     * @param  array{id_klien?: int, posisi: string, jumlah_kebutuhan: int, kualifikasi?: string|null, pengalaman_min?: int, sla_deadline: string, status_req?: string, kompetensi: list<int>}  $data
     */
    public function buat(User $user, array $data): JobRequirement
    {
        return DB::transaction(function () use ($user, $data) {
            $job = JobRequirement::create([
                ...Arr::only($data, ['posisi', 'jumlah_kebutuhan', 'kualifikasi', 'pengalaman_min', 'sla_deadline']),
                'id_klien' => $user->hasRole(Role::HrClient) ? $user->idKlien() : $data['id_klien'],
                'id_am' => $user->hasRole(Role::AccountManager) ? $user->pegawai->id_pegawai : null,
                'status_req' => $data['status_req'] ?? StatusReq::Draft->value,
            ]);
            $job->kompetensi()->sync($data['kompetensi']);

            return $this->detail($job);
        });
    }

    /**
     * Isi job hanya bisa diubah saat draft/diajukan. `kompetensi`, jika dikirim, menggantikan daftar lama.
     */
    public function ubah(JobRequirement $job, array $data): JobRequirement
    {
        if (! in_array($job->status_req, self::STATUS_BISA_DIUBAH, true)) {
            throw ValidationException::withMessages([
                'status_req' => 'Job requirement hanya dapat diubah saat berstatus draft atau diajukan.',
            ]);
        }

        return DB::transaction(function () use ($job, $data) {
            $job->update(Arr::only($data, ['posisi', 'jumlah_kebutuhan', 'kualifikasi', 'pengalaman_min', 'sla_deadline']));
            if (array_key_exists('kompetensi', $data)) {
                $job->kompetensi()->sync($data['kompetensi']);
            }

            return $this->detail($job);
        });
    }

    /**
     * Ajukan (draft → diajukan) atau batalkan (draft/diajukan/diproses → dibatalkan).
     * Membatalkan job melepas talent yang sedang diproses untuk job ini kembali menjadi tersedia.
     */
    public function ubahStatus(JobRequirement $job, StatusReq $status): JobRequirement
    {
        if ($status === StatusReq::Diajukan) {
            if ($job->status_req !== StatusReq::Draft) {
                throw ValidationException::withMessages(['status_req' => 'Hanya job requirement berstatus draft yang dapat diajukan.']);
            }
            if ($job->sla_deadline->isPast()) {
                throw ValidationException::withMessages(['status_req' => 'SLA deadline sudah lewat. Perbarui SLA deadline sebelum mengajukan.']);
            }
        }

        if ($status === StatusReq::Dibatalkan && ! in_array($job->status_req, self::STATUS_BISA_DIBATALKAN, true)) {
            throw ValidationException::withMessages(['status_req' => 'Job requirement yang sudah terpenuhi atau dibatalkan tidak dapat dibatalkan.']);
        }

        return DB::transaction(function () use ($job, $status) {
            if ($status === StatusReq::Dibatalkan) {
                $this->lepasTalentDalamProses($job);
            }

            $job->update(['status_req' => $status]);

            return $this->detail($job);
        });
    }

    /**
     * Talent yang masih diproses untuk job ini (screening belum tidak_lolos) dikembalikan menjadi tersedia.
     * Dipakai saat job dibatalkan dan saat job terpenuhi oleh placement (R19).
     */
    public function lepasTalentDalamProses(JobRequirement $job): void
    {
        Talent::query()
            ->where('status_kandidat', StatusKandidat::DalamProses)
            ->whereHas('screenings', fn (Builder $q) => $q
                ->where('id_job_req', $job->id_job_req)
                ->where('hasil_screening', '!=', HasilScreening::TidakLolos))
            ->update(['status_kandidat' => StatusKandidat::Tersedia]);
    }

    /** Job yang boleh dilihat user: HR Client hanya milik kliennya. */
    private function milik(User $user): Builder
    {
        return JobRequirement::query()
            ->when($user->hasRole(Role::HrClient), fn (Builder $q) => $q->where('id_klien', $user->idKlien()));
    }
}
