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
     * R33: kompetensi baru dari form job requirement. Nama yang sama setelah normalisasi ("React.js" = "reactjs" = "React JS")
     * memakai kompetensi yang sudah ada; selebihnya dibuat dengan kategori kosong. Dipanggil di dalam transaksi job requirement.
     *
     * @param  list<string>  $daftarNama
     * @return list<int> id kompetensi, unik
     */
    public function cariAtauBuat(array $daftarNama): array
    {
        if ($daftarNama === []) {
            return [];
        }

        // Jika beberapa kompetensi lama punya kunci yang sama, yang paling lama dipakai.
        $master = Kompetensi::query()
            ->orderByDesc('id_kompetensi')
            ->get(['id_kompetensi', 'nama_kompetensi'])
            ->keyBy(fn (Kompetensi $k) => self::kunciNama($k->nama_kompetensi));

        $id = [];
        foreach ($daftarNama as $nama) {
            $nama = self::rapikanNama($nama);
            $kunci = self::kunciNama($nama);
            if ($kunci === '') {
                continue;
            }

            $kompetensi = $master->get($kunci) ?? Kompetensi::create(['nama_kompetensi' => $nama, 'kategori' => null]);
            $master->put($kunci, $kompetensi);
            $id[] = $kompetensi->id_kompetensi;
        }

        return array_values(array_unique($id));
    }

    /** Trim dan rapikan spasi ganda; dipakai sebagai nama kompetensi baru. */
    public static function rapikanNama(string $nama): string
    {
        return preg_replace('/\s+/u', ' ', trim($nama));
    }

    /** Kunci pencocokan: huruf kecil tanpa spasi, titik, tanda hubung, dan garis bawah. */
    public static function kunciNama(string $nama): string
    {
        return preg_replace('/[\s._-]+/u', '', mb_strtolower($nama));
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
