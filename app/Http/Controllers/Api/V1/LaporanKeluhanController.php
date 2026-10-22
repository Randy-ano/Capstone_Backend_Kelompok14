<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusLaporan;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanKeluhan\IndexLaporanKeluhanRequest;
use App\Http\Requests\LaporanKeluhan\StoreLaporanKeluhanRequest;
use App\Http\Requests\LaporanKeluhan\TindakLanjutRequest;
use App\Http\Resources\LaporanKeluhanResource;
use App\Models\LaporanKeluhan;
use App\Services\LaporanKeluhanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Laporan keluhan klien (FR-19, R24). HR Client melapor untuk talent di kliennya; AM & Manager menindaklanjuti.
 */
class LaporanKeluhanController extends Controller
{
    public function __construct(private readonly LaporanKeluhanService $laporan) {}

    /**
     * Daftar laporan keluhan.
     *
     * `meta.ringkasan` berisi jumlah per status dari seluruh laporan yang dapat dilihat, tidak terpengaruh filter.
     */
    public function index(IndexLaporanKeluhanRequest $request): AnonymousResourceCollection
    {
        return LaporanKeluhanResource::collection($this->laporan->daftar($request->user(), $request->validated()))
            ->additional([
                'message' => 'Berhasil',
                'meta' => ['ringkasan' => $this->laporan->ringkasan($request->user())],
            ]);
    }

    /**
     * Buat laporan keluhan.
     */
    public function store(StoreLaporanKeluhanRequest $request): JsonResponse
    {
        return (new LaporanKeluhanResource($this->laporan->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Laporan keluhan berhasil dikirim.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Tindak lanjuti laporan.
     *
     * Ubah status menjadi diproses atau selesai (hasil wajib saat selesai). Laporan selesai tidak dapat diubah.
     */
    public function tindakLanjut(TindakLanjutRequest $request, LaporanKeluhan $laporanKeluhan): LaporanKeluhanResource
    {
        $laporan = $this->laporan->tindakLanjut($request->user(), $laporanKeluhan, $request->validated());

        return (new LaporanKeluhanResource($laporan))->additional([
            'message' => $laporan->status_laporan === StatusLaporan::Selesai ? 'Laporan ditandai selesai.' : 'Laporan sedang diproses.',
        ]);
    }
}
