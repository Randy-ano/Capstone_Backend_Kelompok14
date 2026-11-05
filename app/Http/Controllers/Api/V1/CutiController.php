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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Pengajuan cuti talent (FR-14, R21). Talent melihat miliknya, HR Client melihat talent di kliennya dan memutuskan;
 * Manager, AM, dan TS hanya melihat. Lama cuti dihitung dalam hari kerja dengan kuota per tahun.
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
     * Kuota cuti saya.
     *
     * Khusus Talent: kuota, terpakai (menunggu + disetujui), dan sisa hari kerja di tahun kalender (default tahun ini).
     *
     * @queryParam tahun integer Tahun kalender. Example: 2026
     *
     * @response {"data": {"tahun": 2026, "kuota": 12, "terpakai": 2, "sisa": 10}, "message": "Berhasil"}
     */
    public function kuota(Request $request): JsonResponse
    {
        $tahun = $request->validate(['tahun' => ['nullable', 'integer', 'min:2000', 'max:2100']])['tahun'] ?? now()->year;

        return response()->json([
            'data' => $this->cuti->kuota($request->user()->talent, (int) $tahun),
            'message' => 'Berhasil',
        ]);
    }

    /**
     * Ajukan cuti.
     *
     * Khusus Talent, untuk penempatan aktif miliknya. Tanggal mulai paling cepat besok, dalam periode penempatan,
     * minimal satu hari kerja, tidak tumpang tindih dengan cuti lain yang menunggu/disetujui, dan tidak melebihi sisa kuota.
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
     * Khusus HR Client dari klien tempat talent ditempatkan; keputusan langsung menjadi status akhir cuti.
     */
    public function approval(ApprovalCutiRequest $request, PengajuanCuti $cuti): CutiResource
    {
        $cuti = $this->cuti->putuskan($request->user(), $cuti, StatusApproval::from($request->validated('keputusan')));

        $pesan = $cuti->status_cuti === StatusCuti::Disetujui ? 'Cuti disetujui.' : 'Cuti ditolak.';

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
