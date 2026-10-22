<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cuti\ApprovalCutiRequest;
use App\Http\Requests\Cuti\IndexCutiRequest;
use App\Http\Requests\Cuti\StoreCutiRequest;
use App\Http\Resources\CutiResource;
use App\Models\PengajuanCuti;
use App\Services\CutiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Pengajuan cuti talent (FR-14, R21). Talent melihat miliknya, HR Client melihat talent di kliennya, Manager semua.
 */
class CutiController extends Controller
{
    public function __construct(private readonly CutiService $cuti) {}

    /**
     * Daftar cuti.
     */
    public function index(IndexCutiRequest $request): AnonymousResourceCollection
    {
        return CutiResource::collection($this->cuti->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Ajukan cuti.
     *
     * Khusus Talent, untuk placement aktif miliknya; tanggal tidak boleh tumpang tindih dengan cuti lain yang menunggu/disetujui.
     */
    public function store(StoreCutiRequest $request): JsonResponse
    {
        return (new CutiResource($this->cuti->ajukan($request->user(), $request->validated())))
            ->additional(['message' => 'Pengajuan cuti berhasil dikirim.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Putuskan cuti.
     *
     * HR Client (klien yang sama) dan Manager memberi keputusan masing-masing; cuti disetujui jika keduanya setuju.
     */
    public function approval(ApprovalCutiRequest $request, PengajuanCuti $cuti): CutiResource
    {
        $cuti = $this->cuti->putuskan($request->user(), $cuti, StatusApproval::from($request->validated('keputusan')));

        $pesan = match ($cuti->status_cuti) {
            StatusCuti::Disetujui => 'Cuti disetujui.',
            StatusCuti::Ditolak => 'Cuti ditolak.',
            default => 'Keputusan disimpan. Menunggu keputusan pihak lain.',
        };

        return (new CutiResource($cuti))->additional(['message' => $pesan]);
    }

    /**
     * Batalkan cuti.
     *
     * Talent pemilik, selama cuti masih menunggu.
     */
    public function batal(PengajuanCuti $cuti): CutiResource
    {
        return (new CutiResource($this->cuti->batal($cuti)))->additional(['message' => 'Pengajuan cuti dibatalkan.']);
    }
}
