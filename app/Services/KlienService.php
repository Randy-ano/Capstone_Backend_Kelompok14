<?php

namespace App\Services;

use App\Models\Klien;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar klien (read-only) untuk pilihan klien saat AM membuat job requirement dan filter internal.
 */
class KlienService
{
    /**
     * @param  array{search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return Klien::query()
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where('nama_perusahaan', 'like', "%{$search}%"))
            ->orderBy('nama_perusahaan')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }
}
