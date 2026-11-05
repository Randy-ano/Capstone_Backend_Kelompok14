<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusTampilPlacement;
use App\Enums\TipeMonitoring;
use App\Http\Resources\PlacementResource;
use App\Models\Dokumen;
use App\Models\MonitoringTalent;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-15, FR-18, R23 (revisi 2026-11-06): monitoring kinerja per talent.
 * HR Client mencatat untuk talent di perusahaannya; Manager mencatat atau memberi Surat Peringatan (SP, wajib dokumen)
 * untuk talent mana pun; AM & TS melihat; talent melihat riwayat miliknya. Scope data mengikuti PlacementService::scope.
 */
class MonitoringService
{
    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'pencatat.pegawai', 'pencatat.hrClient', 'dokumen'];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    /**
     * Linimasa catatan & SP (terbaru di atas).
     *
     * @param  array{id_placement?: int|null, tipe?: string|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return MonitoringTalent::query()
            ->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user))
            ->with(self::RELASI)
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
            ->when($filter['tipe'] ?? null, fn (Builder $q, string $tipe) => $q->where('tipe', $tipe))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->whereHas('placement', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('posisi_penempatan', 'like', "%{$search}%")
                ->orWhereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))
                ->orWhereHas('klien', fn (Builder $q) => $q->where('nama_perusahaan', 'like', "%{$search}%")))))
            ->orderByDesc('tanggal_monitoring')
            ->orderByDesc('id_monitoring')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Kartu per talent: satu kartu per penempatan (sesuai scope) dengan jumlah catatan & SP.
     *
     * @param  array{status?: string|null, id_klien?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function perTalent(User $user, array $filter): LengthAwarePaginator
    {
        if (! $user->isInternal()) {
            unset($filter['id_klien']);
        }

        return PlacementService::scope(Placement::query(), $user)
            ->with(['talent.user', 'klien'])
            ->withCount([
                'monitorings as jumlah_catatan' => fn (Builder $q) => $q->where('tipe', TipeMonitoring::Catatan),
                'monitorings as jumlah_sp' => fn (Builder $q) => $q->where('tipe', TipeMonitoring::Sp),
            ])
            ->when($filter['status'] ?? null, fn (Builder $q, string $s) => $q->statusTampil(StatusTampilPlacement::from($s)))
            ->when($filter['id_klien'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_klien', $id))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->whereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))
                ->orWhere('posisi_penempatan', 'like', "%{$search}%")))
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_placement')
            ->paginate($filter['per_page'] ?? 12)
            ->withQueryString();
    }

    /**
     * Jumlah penempatan per status tampil (sesuai scope, tanpa filter) untuk ringkasan filter.
     *
     * @return array<string, int>
     */
    public function ringkasanStatus(User $user): array
    {
        return collect(StatusTampilPlacement::cases())->mapWithKeys(fn (StatusTampilPlacement $s) => [
            $s->value => PlacementService::scope(Placement::query(), $user)->statusTampil($s)->count(),
        ])->all();
    }

    /**
     * R23 (revisi): HR Client hanya catatan untuk penempatan di kliennya; Manager catatan atau SP. SP wajib dokumen
     * Surat Peringatan milik talent penempatan itu (belum dipakai SP lain, tidak ditolak). Hanya penempatan Aktif.
     *
     * @param  array{id_placement: int, tipe?: string|null, id_dokumen?: int|null, tanggal_monitoring?: string|null, hasil_monitoring: string, catatan_tindak_lanjut?: string|null}  $data
     */
    public function catat(User $user, array $data): MonitoringTalent
    {
        $placement = Placement::with('talent')->findOrFail($data['id_placement']);
        $tipe = TipeMonitoring::from($data['tipe'] ?? TipeMonitoring::Catatan->value);

        if ($user->hasRole(Role::HrClient)) {
            if ($tipe === TipeMonitoring::Sp) {
                throw new AccessDeniedHttpException('Surat Peringatan hanya dapat diberikan oleh Manager.');
            }
            if ($placement->id_klien !== $user->idKlien()) {
                throw new AccessDeniedHttpException('Anda hanya dapat mencatat monitoring untuk talent yang ditempatkan di perusahaan Anda.');
            }
        }
        if ($placement->statusTampil() !== StatusTampilPlacement::Aktif) {
            throw ValidationException::withMessages(['id_placement' => 'Monitoring hanya dapat dicatat untuk penempatan yang sedang aktif.']);
        }
        $idDokumen = $tipe === TipeMonitoring::Sp ? $this->dokumenSp($placement, $data['id_dokumen'] ?? null)->id_dokumen : null;

        $monitoring = $placement->monitorings()->create([
            'id_user_pencatat' => $user->id_user,
            'tipe' => $tipe,
            'id_dokumen' => $idDokumen,
            'tanggal_monitoring' => $data['tanggal_monitoring'] ?? now(),
            'hasil_monitoring' => $data['hasil_monitoring'],
            'catatan_tindak_lanjut' => $data['catatan_tindak_lanjut'] ?? null,
        ]);
        if ($tipe === TipeMonitoring::Sp) {
            $this->pemberitahuan->spDiberikan($monitoring);
        }

        return $monitoring->load(self::RELASI);
    }

    private function dokumenSp(Placement $placement, ?int $idDokumen): Dokumen
    {
        if (! $idDokumen) {
            throw ValidationException::withMessages(['id_dokumen' => 'Surat Peringatan wajib melampirkan dokumen Surat Peringatan.']);
        }
        $dokumen = Dokumen::find($idDokumen);
        if (! $dokumen || $dokumen->jenis_dokumen !== JenisDokumen::SuratPeringatan || $dokumen->id_talent !== $placement->id_talent) {
            throw ValidationException::withMessages(['id_dokumen' => 'Pilih dokumen berjenis Surat Peringatan milik talent ini.']);
        }
        if ($dokumen->status_dokumen === StatusDokumen::Ditolak) {
            throw ValidationException::withMessages(['id_dokumen' => 'Dokumen Surat Peringatan yang ditolak tidak dapat dipakai.']);
        }
        if (MonitoringTalent::where('id_dokumen', $dokumen->id_dokumen)->exists()) {
            throw ValidationException::withMessages(['id_dokumen' => 'Dokumen Surat Peringatan ini sudah dipakai untuk SP lain.']);
        }

        return $dokumen;
    }
}
