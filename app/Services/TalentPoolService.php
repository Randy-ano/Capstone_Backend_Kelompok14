<?php

namespace App\Services;

use App\Enums\StatusKetersediaan;
use App\Models\TalentPool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * FR-06: talent pool dilihat oleh internal (AM/TS/Manager) dan dikelola TS.
 * Entri baru dibuat otomatis oleh TalentObserver (R5), bukan lewat endpoint.
 */
class TalentPoolService
{
    /** Relasi talent yang ditampilkan di daftar talent pool. */
    private const RELASI_DAFTAR = ['talent.user', 'talent.kompetensi', 'talent.riwayatPendidikan'];

    /**
     * @param  array{search?: string|null, status?: string|null, kompetensi?: list<int>|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return TalentPool::query()
            ->with(self::RELASI_DAFTAR)
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_ketersediaan', $status))
            // Talent harus memiliki semua kompetensi yang dipilih.
            ->when($filter['kompetensi'] ?? null, function (Builder $q, array $kompetensi) {
                foreach ($kompetensi as $id) {
                    $q->whereHas('talent.kompetensi', fn (Builder $q) => $q->whereKey((int) $id));
                }
            })
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->where('tag_keahlian', 'like', "%{$search}%")
                ->orWhereHas('talent', fn (Builder $q) => $q
                    ->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('domisili', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $q) => $q->where('email', 'like', "%{$search}%"))
                    ->orWhereHas('kompetensi', fn (Builder $q) => $q->where('nama_kompetensi', 'like', "%{$search}%")))))
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id_talent_pool')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Jumlah entri per status dari seluruh talent pool (tidak terpengaruh filter/pencarian/halaman).
     *
     * @return array{total: int, tersedia: int, tidak_tersedia: int}
     */
    public function ringkasan(): array
    {
        $perStatus = TalentPool::query()
            ->selectRaw('status_ketersediaan, count(*) as jumlah')
            ->groupBy('status_ketersediaan')
            ->pluck('jumlah', 'status_ketersediaan');

        $ringkasan = ['total' => (int) $perStatus->sum()];
        foreach (StatusKetersediaan::cases() as $status) {
            $ringkasan[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        return $ringkasan;
    }

    public function detail(TalentPool $talentPool): TalentPool
    {
        return $talentPool->load([
            ...self::RELASI_DAFTAR,
            'talent.pengalamanKerjaTalent',
            'talent.sertifikat',
            'talent.screenings' => fn ($q) => $q->with('jobRequirement.klien')->latest('tanggal_screening'),
        ]);
    }

    /**
     * @param  array{status_ketersediaan?: string, tag_keahlian?: string|null}  $data
     */
    public function ubah(TalentPool $talentPool, array $data): TalentPool
    {
        $talentPool->update(Arr::only($data, ['status_ketersediaan', 'tag_keahlian']));

        return $this->detail($talentPool);
    }
}
