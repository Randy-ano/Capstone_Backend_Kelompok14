<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kompetensi\IndexKompetensiRequest;
use App\Http\Requests\Kompetensi\StoreKompetensiRequest;
use App\Http\Requests\Kompetensi\UpdateKompetensiRequest;
use App\Http\Resources\KompetensiResource;
use App\Models\Kompetensi;
use App\Services\KompetensiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Profil Talent & Kompetensi
 */
class KompetensiController extends Controller
{
    public function __construct(private readonly KompetensiService $kompetensi) {}

    /**
     * Daftar kompetensi.
     *
     * Master kompetensi untuk pilihan di profil talent, job requirement, dan filter talent pool. Semua role.
     * `meta.kategori` berisi semua kategori yang sudah dipakai. `dengan_pemakaian=1` menambahkan jumlah talent & job requirement per kompetensi.
     */
    public function index(IndexKompetensiRequest $request): AnonymousResourceCollection
    {
        return KompetensiResource::collection($this->kompetensi->daftar($request->validated()))
            ->additional([
                'message' => 'Berhasil',
                'meta' => ['kategori' => $this->kompetensi->daftarKategori()],
            ]);
    }

    /**
     * Tambah kompetensi.
     *
     * Khusus Talent Support dan Manager. Nama unik tanpa membedakan huruf besar/kecil.
     */
    public function store(StoreKompetensiRequest $request): JsonResponse
    {
        return (new KompetensiResource($this->kompetensi->buat($request->validated())))
            ->additional(['message' => 'Kompetensi berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ubah kompetensi.
     *
     * Khusus Talent Support dan Manager. Perubahan nama langsung berlaku di profil talent dan job requirement yang memakainya.
     */
    public function update(UpdateKompetensiRequest $request, Kompetensi $kompetensi): KompetensiResource
    {
        return (new KompetensiResource($this->kompetensi->ubah($kompetensi, $request->validated())))
            ->additional(['message' => 'Kompetensi berhasil diperbarui.']);
    }

    /**
     * Hapus kompetensi.
     *
     * Khusus Talent Support dan Manager. Ditolak (422) jika masih dipakai talent atau job requirement.
     */
    public function destroy(Kompetensi $kompetensi): JsonResponse
    {
        $this->kompetensi->hapus($kompetensi);

        return response()->json(['data' => null, 'message' => 'Kompetensi berhasil dihapus.']);
    }
}
