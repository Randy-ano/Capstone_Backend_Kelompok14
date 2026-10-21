<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pengajuan\IndexPengajuanRequest;
use App\Http\Requests\Pengajuan\StorePengajuanRequest;
use App\Http\Requests\Pengajuan\UpdateStatusPengajuanRequest;
use App\Http\Resources\PengajuanResource;
use App\Models\PengajuanKandidat;
use App\Services\PengajuanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Pengajuan Kandidat
 *
 * Pengajuan kandidat ke klien (FR-10). Talent melihat miliknya, HR Client melihat pengajuan ke job kliennya, internal semua.
 */
class PengajuanController extends Controller
{
    public function __construct(private readonly PengajuanService $pengajuan) {}

    /**
     * Daftar pengajuan.
     */
    public function index(IndexPengajuanRequest $request): AnonymousResourceCollection
    {
        return PengajuanResource::collection($this->pengajuan->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Ajukan kandidat.
     *
     * Khusus Account Manager. Hanya dari screening lolos yang belum diajukan (R14); job harus berstatus diproses.
     */
    public function store(StorePengajuanRequest $request): JsonResponse
    {
        return (new PengajuanResource($this->pengajuan->buat($request->user(), $request->validated('id_screening'))))
            ->additional(['message' => 'Kandidat berhasil diajukan ke klien.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail pengajuan.
     *
     * Beserta jadwal interview dan evaluasi.
     */
    public function show(PengajuanKandidat $pengajuan): PengajuanResource
    {
        return (new PengajuanResource($this->pengajuan->detail($pengajuan)))->additional(['message' => 'Berhasil']);
    }

    /**
     * Tinjau pengajuan.
     *
     * Khusus HR Client klien pemilik job: lanjut_interview atau ditolak (R15). Ditolak → talent kembali tersedia.
     */
    public function updateStatus(UpdateStatusPengajuanRequest $request, PengajuanKandidat $pengajuan): PengajuanResource
    {
        $status = StatusPengajuan::from($request->validated('status_pengajuan'));

        return (new PengajuanResource($this->pengajuan->ubahStatus($pengajuan, $status)))
            ->additional(['message' => $status === StatusPengajuan::Ditolak ? 'Kandidat ditolak.' : 'Kandidat dilanjutkan ke tahap interview.']);
    }
}
