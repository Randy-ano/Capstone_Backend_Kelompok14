<?php

namespace App\Services;

use App\Models\Kompetensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Master kompetensi: dibaca semua role, dikelola TS dan Manager.
 */
class KompetensiService
{
    /**
     * @param  array{search?: string|null, kategori?: string|null, dengan_pemakaian?: bool|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return Kompetensi::query()
            ->when($filter['dengan_pemakaian'] ?? false, fn (Builder $q) => $q->withCount(['talents', 'jobRequirements']))
            ->when($filter['search'] ?? null, fn ($q, $search) => $q->where('nama_kompetensi', 'like', "%{$search}%"))
            ->when($filter['kategori'] ?? null, fn ($q, $kategori) => $q->where('kategori', $kategori))
            ->orderBy('nama_kompetensi')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Daftar kategori yang sudah dipakai, untuk pilihan filter dan isian kategori.
     *
     * @return list<string>
     */
    public function daftarKategori(): array
    {
        return Kompetensi::query()
            ->whereNotNull('kategori')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->all();
    }

    /**
     * @param  array{nama_kompetensi: string, kategori?: string|null}  $data
     */
    public function buat(array $data): Kompetensi
    {
        return Kompetensi::create($data)->loadCount(['talents', 'jobRequirements']);
    }

    /**
     * @param  array{nama_kompetensi?: string, kategori?: string|null}  $data
     */
    public function ubah(Kompetensi $kompetensi, array $data): Kompetensi
    {
        $kompetensi->update($data);

        return $kompetensi->loadCount(['talents', 'jobRequirements']);
    }

    /**
     * Kompetensi yang masih dipakai talent atau job requirement tidak boleh dihapus: foreign key-nya cascade,
     * sehingga penghapusan akan diam-diam mengubah profil talent (R4) dan syarat job requirement.
     */
    public function hapus(Kompetensi $kompetensi): void
    {
        $kompetensi->loadCount(['talents', 'jobRequirements']);

        if ($kompetensi->talents_count > 0 || $kompetensi->job_requirements_count > 0) {
            throw ValidationException::withMessages([
                'id_kompetensi' => "Kompetensi {$kompetensi->nama_kompetensi} masih dipakai oleh {$kompetensi->talents_count} talent dan {$kompetensi->job_requirements_count} job requirement, sehingga tidak dapat dihapus.",
            ]);
        }

        $kompetensi->delete();
    }
}
