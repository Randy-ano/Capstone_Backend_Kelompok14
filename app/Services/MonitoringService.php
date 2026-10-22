<?php

namespace App\Services;

use App\Http\Resources\PlacementResource;
use App\Models\MonitoringTalent;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-15, FR-18, R23: monitoring kinerja talent dicatat Manager untuk placement mana pun; dilihat Manager & AM,
 * dan talent untuk placement miliknya saja (read-only).
 */
class MonitoringService
{
    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'manager'];

    /**
     * @param  array{id_placement?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return MonitoringTalent::query()
            ->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user))
            ->with(self::RELASI)
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
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
     * @param  array{id_placement: int, tanggal_monitoring?: string|null, hasil_monitoring: string, catatan_tindak_lanjut?: string|null}  $data
     */
    public function catat(User $user, array $data): MonitoringTalent
    {
        $placement = Placement::findOrFail($data['id_placement']);

        $monitoring = $placement->monitorings()->create([
            'id_manager' => $user->pegawai->id_pegawai,
            'tanggal_monitoring' => $data['tanggal_monitoring'] ?? now(),
            'hasil_monitoring' => $data['hasil_monitoring'],
            'catatan_tindak_lanjut' => $data['catatan_tindak_lanjut'] ?? null,
        ]);

        return $monitoring->load(self::RELASI);
    }
}
