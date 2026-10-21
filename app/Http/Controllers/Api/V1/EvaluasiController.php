<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluasi\StoreEvaluasiRequest;
use App\Http\Resources\EvaluasiResource;
use App\Models\EvaluasiKandidat;
use App\Services\EvaluasiService;
use Illuminate\Http\JsonResponse;

/**
 * @group Evaluasi Kandidat
 *
 * Evaluasi kandidat oleh HR Client setelah interview (FR-12).
 */
class EvaluasiController extends Controller
{
    public function __construct(private readonly EvaluasiService $evaluasi) {}

    /**
     * Simpan evaluasi.
     *
     * Khusus HR Client klien pemilik job; maksimal 1 per pengajuan, setelah minimal 1 interview selesai (R17).
     */
    public function store(StoreEvaluasiRequest $request): JsonResponse
    {
        return (new EvaluasiResource($this->evaluasi->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Evaluasi berhasil disimpan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail evaluasi.
     */
    public function show(EvaluasiKandidat $evaluasi): EvaluasiResource
    {
        return (new EvaluasiResource($this->evaluasi->detail($evaluasi)))->additional(['message' => 'Berhasil']);
    }
}
