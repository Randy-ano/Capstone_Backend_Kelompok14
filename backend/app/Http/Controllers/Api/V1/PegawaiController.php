<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pegawai\IndexPegawaiRequest;
use App\Http\Requests\Pegawai\StorePegawaiRequest;
use App\Http\Requests\Pegawai\UpdatePegawaiRequest;
use App\Http\Resources\PegawaiResource;
use App\Models\Pegawai;
use App\Services\PegawaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Auth & Akun
 *
 * Kelola akun internal PT Intellisys (AM, TS, Manager). Khusus Manager.
 */
class PegawaiController extends Controller
{
    public function __construct(private readonly PegawaiService $pegawai) {}

    /**
     * Daftar pegawai.
     */
    public function index(IndexPegawaiRequest $request): AnonymousResourceCollection
    {
        return PegawaiResource::collection($this->pegawai->daftar($request->validated()));
    }

    /**
     * Tambah pegawai.
     *
     * Membuat akun user (role internal) beserta profil pegawai.
     */
    public function store(StorePegawaiRequest $request): JsonResponse
    {
        $pegawai = $this->pegawai->buat($request->validated());

        return (new PegawaiResource($pegawai))
            ->additional(['message' => 'Pegawai berhasil ditambahkan'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail pegawai.
     */
    public function show(Pegawai $pegawai): PegawaiResource
    {
        return (new PegawaiResource($pegawai->load('user')))->additional(['message' => 'Berhasil']);
    }

    /**
     * Ubah pegawai.
     *
     * Semua kolom opsional; hanya yang dikirim yang diubah.
     */
    public function update(UpdatePegawaiRequest $request, Pegawai $pegawai): PegawaiResource
    {
        $pegawai = $this->pegawai->ubah($pegawai, $request->validated());

        return (new PegawaiResource($pegawai))->additional(['message' => 'Pegawai berhasil diperbarui']);
    }
}
