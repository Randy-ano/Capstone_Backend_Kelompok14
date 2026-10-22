<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusPlacement;
use App\Http\Resources\PlacementResource;
use App\Models\PengajuanCuti;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * FR-14, R21: cuti diajukan talent untuk placement aktif miliknya, diputuskan HR Client (klien yang sama)
 * dan Manager. Disetujui jika keduanya setuju, ditolak jika salah satu menolak.
 */
class CutiService
{
    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'approverKlien', 'approverManager'];

    /** Talent miliknya, HR Client cuti talent yang ditempatkan di kliennya, Manager semua. */
    public static function scope(Builder $query, User $user): Builder
    {
        return $query->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user));
    }

    /**
     * @param  array{status?: string|null, id_placement?: int|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(PengajuanCuti::query(), $user)
            ->with(self::RELASI)
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_cuti', $status))
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_cuti')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @param  array{id_placement: int, tanggal_mulai: string, tanggal_selesai: string, alasan_cuti: string}  $data
     */
    public function ajukan(User $user, array $data): PengajuanCuti
    {
        $placement = Placement::findOrFail($data['id_placement']);
        $talent = $user->talent;

        if ($placement->id_talent !== $talent->id_talent) {
            throw ValidationException::withMessages(['id_placement' => 'Cuti hanya dapat diajukan untuk placement milik Anda.']);
        }
        if ($placement->status_placement !== StatusPlacement::Aktif) {
            throw ValidationException::withMessages(['id_placement' => 'Cuti hanya dapat diajukan untuk placement yang masih aktif.']);
        }

        $bentrok = PengajuanCuti::query()
            ->where('id_talent', $talent->id_talent)
            ->whereIn('status_cuti', [StatusCuti::Menunggu, StatusCuti::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $data['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $data['tanggal_mulai'])
            ->exists();
        if ($bentrok) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Tanggal cuti bertumpang tindih dengan cuti lain yang masih menunggu atau sudah disetujui.']);
        }

        $cuti = PengajuanCuti::create([
            'id_talent' => $talent->id_talent,
            'id_placement' => $placement->id_placement,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'alasan_cuti' => $data['alasan_cuti'],
        ]);

        return $cuti->load(self::RELASI);
    }

    /**
     * HR Client memutuskan sisi klien, Manager sisi manager; status_cuti dihitung ulang (R21).
     */
    public function putuskan(User $user, PengajuanCuti $cuti, StatusApproval $keputusan): PengajuanCuti
    {
        if ($cuti->status_cuti !== StatusCuti::Menunggu) {
            throw ValidationException::withMessages(['keputusan' => "Cuti ini sudah {$cuti->status_cuti->value}; keputusan tidak dapat diubah."]);
        }

        $klien = $user->hasRole(Role::HrClient);
        $kolom = $klien ? 'status_approval_klien' : 'status_approval_manager';
        if ($cuti->{$kolom} !== StatusApproval::Menunggu) {
            throw ValidationException::withMessages(['keputusan' => 'Anda sudah memberikan keputusan untuk cuti ini.']);
        }

        $cuti->fill($klien
            ? ['status_approval_klien' => $keputusan, 'id_approver_klien' => $user->hrClient->id_hr_client]
            : ['status_approval_manager' => $keputusan, 'id_approver_manager' => $user->pegawai->id_pegawai]);
        $cuti->status_cuti = self::statusCuti($cuti->status_approval_klien, $cuti->status_approval_manager);
        $cuti->save();

        return $cuti->load(self::RELASI);
    }

    /** Talent membatalkan cutinya selama masih menunggu (R21). */
    public function batal(PengajuanCuti $cuti): PengajuanCuti
    {
        if ($cuti->status_cuti !== StatusCuti::Menunggu) {
            throw ValidationException::withMessages(['status_cuti' => 'Hanya cuti yang masih menunggu yang dapat dibatalkan.']);
        }
        $cuti->update(['status_cuti' => StatusCuti::Dibatalkan]);

        return $cuti->load(self::RELASI);
    }

    public static function statusCuti(StatusApproval $klien, StatusApproval $manager): StatusCuti
    {
        return match (true) {
            $klien === StatusApproval::Ditolak || $manager === StatusApproval::Ditolak => StatusCuti::Ditolak,
            $klien === StatusApproval::Disetujui && $manager === StatusApproval::Disetujui => StatusCuti::Disetujui,
            default => StatusCuti::Menunggu,
        };
    }
}
