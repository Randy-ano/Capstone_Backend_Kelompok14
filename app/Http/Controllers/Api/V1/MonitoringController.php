<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\IndexMonitoringRequest;
use App\Http\Requests\Monitoring\StoreMonitoringRequest;
use App\Http\Requests\Monitoring\IndexMonitoringTalentRequest;
use App\Http\Resources\MonitoringResource;
use App\Http\Resources\MonitoringTalentResource;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Monitoring kinerja per talent (FR-15, FR-18, R23, revisi 2026-11-06). HR Client mencatat untuk talent di perusahaannya;
 * Manager mencatat atau memberi Surat Peringatan; AM & TS melihat; talent melihat riwayat miliknya.
 */
class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringService $monitoring) {}

    /**
     * Daftar monitoring.
     *
     * Talent hanya menerima catatan untuk penempatan miliknya.
     */
    public function index(IndexMonitoringRequest $request): AnonymousResourceCollection
    {
        return MonitoringResource::collection($this->monitoring->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Kartu monitoring per talent.
     *
     * Satu kartu per penempatan sesuai scope: status kontrak (status_tampil), periode, sisa hari, jumlah catatan & SP.
     * Filter `status` (belum_aktif/aktif/selesai), `id_klien` (internal), `search` (nama talent/posisi).
     */
    public function perTalent(IndexMonitoringTalentRequest $request): AnonymousResourceCollection
    {
        return MonitoringTalentResource::collection($this->monitoring->perTalent($request->user(), $request->validated()))
            ->additional(['meta' => ['ringkasan' => $this->monitoring->ringkasanStatus($request->user())], 'message' => 'Berhasil']);
    }

    /**
     * Catat monitoring atau beri Surat Peringatan.
     *
     * HR Client: catatan untuk talent di perusahaannya. Manager: catatan atau SP (`tipe` = sp, wajib `id_dokumen`
     * Surat Peringatan milik talent). Hanya untuk penempatan Aktif. SP dinotifikasi ke talent & HR Client.
     */
    public function store(StoreMonitoringRequest $request): JsonResponse
    {
        return (new MonitoringResource($this->monitoring->catat($request->user(), $request->validated())))
            ->additional(['message' => $request->validated('tipe') === 'sp' ? 'Surat Peringatan berhasil dicatat.' : 'Catatan monitoring berhasil disimpan.'])
            ->response()
            ->setStatusCode(201);
    }
}
