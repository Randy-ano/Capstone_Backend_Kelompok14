<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Screening\IndexScreeningRequest;
use App\Http\Requests\Screening\StoreScreeningRequest;
use App\Http\Requests\Screening\UpdateScreeningRequest;
use App\Http\Resources\ScreeningResource;
use App\Models\ScreeningTalent;
use App\Services\ScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Screening
 *
 * Screening kandidat (FR-08). Dilihat TS, AM, Manager; dibuat dan diubah oleh Talent Support.
 */
class ScreeningController extends Controller
{
    public function __construct(private readonly ScreeningService $screening) {}

    /**
     * Daftar screening.
     */
    public function index(IndexScreeningRequest $request): AnonymousResourceCollection
    {
        return ScreeningResource::collection($this->screening->daftar($request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Buat screening.
     *
     * Skor kecocokan dihitung otomatis (R9). Talent menjadi "dalam_proses" dan job "diajukan" menjadi "diproses" (R10).
     */
    public function store(StoreScreeningRequest $request): JsonResponse
    {
        return (new ScreeningResource($this->screening->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Screening berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ubah hasil/catatan screening.
     *
     * Hasil "tidak_lolos" mengembalikan talent menjadi tersedia (R11) dan bersifat final.
     */
    public function update(UpdateScreeningRequest $request, ScreeningTalent $screening): ScreeningResource
    {
        return (new ScreeningResource($this->screening->ubah($screening, $request->validated())))
            ->additional(['message' => 'Screening berhasil diperbarui.']);
    }
}
