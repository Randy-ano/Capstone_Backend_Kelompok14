<?php

namespace App\Services;

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusPengajuan;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\PengajuanKandidat;
use App\Models\ScreeningTalent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-10: pengajuan kandidat ke klien oleh AM (R14) dan peninjauan oleh HR Client (R15).
 * Talent hanya melihat pengajuan miliknya, HR Client hanya pengajuan ke job kliennya.
 */
class PengajuanService
{
    public const RELASI = ['talent.user', 'talent.riwayatPendidikan', 'jobRequirement.klien', 'accountManager', 'screening', 'evaluasi.placement'];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    /**
     * Batasi query pengajuan sesuai role (bagian 5.2 spec).
     */
    public static function scope(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole(Role::Talent) => $query->where('id_talent', $user->talent?->id_talent),
            $user->hasRole(Role::HrClient) => $query->whereHas('jobRequirement', fn (Builder $q) => $q->where('id_klien', $user->idKlien())),
            default => $query,
        };
    }

    /**
     * Proses kandidat (pengajuan, interview, evaluasi, placement) hanya untuk job yang masih diproses.
     */
    public static function pastikanJobBerjalan(JobRequirement $job, string $field): void
    {
        if ($job->status_req !== StatusReq::Diproses) {
            throw ValidationException::withMessages([
                $field => "Job requirement berstatus {$job->status_req->value}; proses kandidat tidak dapat dilanjutkan.",
            ]);
        }
    }

    /**
     * @param  array{status?: string|null, id_job_req?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(PengajuanKandidat::query(), $user)
            ->with(self::RELASI)
            ->withCount('interviews')
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_pengajuan', $status))
            ->when($filter['id_job_req'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_job_req', $id))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->whereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))
                ->orWhereHas('jobRequirement', fn (Builder $q) => $q->where('posisi', 'like', "%{$search}%"))))
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id_pengajuan')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    public function detail(PengajuanKandidat $pengajuan): PengajuanKandidat
    {
        return $pengajuan->load([...self::RELASI, 'interviews' => fn ($q) => $q->orderBy('tanggal_interview')->orderBy('waktu_interview'), 'evaluasi.hrClient'])
            ->loadCount('interviews');
    }

    /**
     * R14: hanya dari screening lolos yang belum diajukan; talent & job disalin dari screening.
     */
    public function buat(User $user, int $idScreening): PengajuanKandidat
    {
        $screening = ScreeningTalent::with('jobRequirement')->findOrFail($idScreening);

        if ($screening->hasil_screening !== HasilScreening::Lolos) {
            throw ValidationException::withMessages(['id_screening' => 'Hanya kandidat yang lolos screening yang dapat diajukan ke klien.']);
        }
        if ($screening->pengajuan()->exists()) {
            throw ValidationException::withMessages(['id_screening' => 'Kandidat ini sudah diajukan ke klien.']);
        }
        self::pastikanJobBerjalan($screening->jobRequirement, 'id_screening');

        $pengajuan = PengajuanKandidat::create([
            'id_screening' => $screening->id_screening,
            'id_talent' => $screening->id_talent,
            'id_job_req' => $screening->id_job_req,
            'id_am' => $user->pegawai->id_pegawai,
            'tanggal_pengajuan' => now(),
        ]);
        $this->pemberitahuan->pengajuanDibuat($pengajuan);

        return $this->detail($pengajuan);
    }

    /**
     * R15: HR Client meninjau pengajuan berstatus diajukan. Ditolak → talent kembali tersedia.
     */
    public function ubahStatus(PengajuanKandidat $pengajuan, StatusPengajuan $status): PengajuanKandidat
    {
        if ($pengajuan->status_pengajuan !== StatusPengajuan::Diajukan) {
            throw ValidationException::withMessages(['status_pengajuan' => 'Pengajuan ini sudah ditinjau.']);
        }
        self::pastikanJobBerjalan($pengajuan->jobRequirement, 'status_pengajuan');

        return DB::transaction(function () use ($pengajuan, $status) {
            $pengajuan->update(['status_pengajuan' => $status]);

            if ($status === StatusPengajuan::Ditolak) {
                $pengajuan->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);
            }
            $this->pemberitahuan->statusPengajuan($pengajuan);

            return $this->detail($pengajuan);
        });
    }
}
