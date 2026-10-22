<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Models\EvaluasiKandidat;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR-17: placement talent ke klien (R18–R20). Dibuat TS dari evaluasi diterima; status diakhiri TS/Manager.
 */
class PlacementService
{
    public const RELASI = ['talent.user', 'klien', 'jobRequirement'];

    public function __construct(
        private readonly JobRequirementService $jobRequirement,
        private readonly PemberitahuanService $pemberitahuan,
    ) {}

    /** Batasi query placement sesuai role: talent miliknya, HR Client kliennya, internal semua. */
    public static function scope(Builder $query, User $user): Builder
    {
        return match (true) {
            $user->hasRole(Role::Talent) => $query->where('id_talent', $user->talent?->id_talent),
            $user->hasRole(Role::HrClient) => $query->where('id_klien', $user->idKlien()),
            default => $query,
        };
    }

    /**
     * @param  array{status?: string|null, id_klien?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        if ($user->hasRole(Role::HrClient, Role::Talent)) {
            unset($filter['id_klien']);
        }

        return self::scope(Placement::query(), $user)
            ->with(self::RELASI)
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_placement', $status))
            ->when($filter['id_klien'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_klien', $id))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->where('posisi_penempatan', 'like', "%{$search}%")
                ->orWhereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))
                ->orWhereHas('klien', fn (Builder $q) => $q->where('nama_perusahaan', 'like', "%{$search}%"))))
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_placement')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /** Detail + ringkasan cuti, shift, monitoring, dan keluhan (dipakai Fase 6). */
    public function detail(Placement $placement): Placement
    {
        return $placement->load([...self::RELASI, 'evaluasi'])
            ->loadCount(['cuti', 'shifts', 'monitorings', 'laporanKeluhan']);
    }

    /**
     * R18: hanya dari evaluasi diterima yang belum punya placement. R19 dalam 1 transaksi: talent ditempatkan,
     * pool tidak tersedia, job terpenuhi jika placement aktif ≥ kebutuhan (kandidat lain yang masih diproses dilepas).
     *
     * @param  array{id_evaluasi: int, tanggal_mulai: string, tanggal_selesai?: string|null, posisi_penempatan?: string|null}  $data
     */
    public function buat(array $data): Placement
    {
        $evaluasi = EvaluasiKandidat::with(['pengajuan.jobRequirement', 'pengajuan.talent.talentPool', 'placement'])->findOrFail($data['id_evaluasi']);
        $pengajuan = $evaluasi->pengajuan;
        $job = $pengajuan->jobRequirement;

        if ($evaluasi->status_evaluasi !== StatusEvaluasi::Diterima) {
            throw ValidationException::withMessages(['id_evaluasi' => 'Placement hanya dapat dibuat dari evaluasi yang diterima.']);
        }
        if ($evaluasi->placement) {
            throw ValidationException::withMessages(['id_evaluasi' => 'Evaluasi ini sudah memiliki placement.']);
        }
        PengajuanService::pastikanJobBerjalan($job, 'id_evaluasi');

        return DB::transaction(function () use ($data, $evaluasi, $pengajuan, $job) {
            $placement = Placement::create([
                'id_evaluasi' => $evaluasi->id_evaluasi,
                'id_talent' => $pengajuan->id_talent,
                'id_job_req' => $job->id_job_req,
                'id_klien' => $job->id_klien,
                'posisi_penempatan' => $data['posisi_penempatan'] ?? $job->posisi,
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            ]);

            $pengajuan->talent->update(['status_kandidat' => StatusKandidat::Ditempatkan]);
            $pengajuan->talent->talentPool?->update(['status_ketersediaan' => StatusKetersediaan::TidakTersedia]);

            $aktif = $job->placements()->where('status_placement', StatusPlacement::Aktif)->count();
            if ($aktif >= $job->jumlah_kebutuhan) {
                $job->update(['status_req' => StatusReq::Terpenuhi]);
                $this->jobRequirement->lepasTalentDalamProses($job);
            }
            $this->pemberitahuan->placementDibuat($placement);

            return $this->detail($placement);
        });
    }

    /**
     * R20: placement aktif diakhiri (selesai/dihentikan) → talent kembali tersedia di pool.
     * Status job tidak dibuka kembali.
     */
    public function ubahStatus(Placement $placement, StatusPlacement $status, ?string $tanggalSelesai): Placement
    {
        if ($placement->status_placement !== StatusPlacement::Aktif) {
            throw ValidationException::withMessages(['status_placement' => 'Hanya placement aktif yang dapat diakhiri.']);
        }
        if ($tanggalSelesai && $placement->tanggal_mulai->gt($tanggalSelesai)) {
            throw ValidationException::withMessages(['tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);
        }

        return DB::transaction(function () use ($placement, $status, $tanggalSelesai) {
            $placement->update([
                'status_placement' => $status,
                'tanggal_selesai' => $tanggalSelesai ?? $placement->tanggal_selesai ?? now()->toDateString(),
            ]);

            $placement->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);
            $placement->talent->talentPool?->update(['status_ketersediaan' => StatusKetersediaan::Tersedia]);

            return $this->detail($placement);
        });
    }
}
