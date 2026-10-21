<?php

namespace App\Services;

use App\Models\Kompetensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Master kompetensi (read-only untuk saat ini; kelola master oleh TS/Manager belum dibuat).
 */
class KompetensiService
{
    /**
     * @param  array{search?: string|null, kategori?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return Kompetensi::query()
            ->when($filter['search'] ?? null, fn ($q, $search) => $q->where('nama_kompetensi', 'like', "%{$search}%"))
            ->when($filter['kategori'] ?? null, fn ($q, $kategori) => $q->where('kategori', $kategori))
            ->orderBy('nama_kompetensi')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }
}
