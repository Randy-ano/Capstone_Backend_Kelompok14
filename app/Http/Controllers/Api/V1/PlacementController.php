<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Placement\IndexPlacementRequest;
use App\Http\Requests\Placement\StorePlacementRequest;
use App\Http\Requests\Placement\UpdateStatusPlacementRequest;
use App\Http\Resources\PlacementResource;
use App\Models\Placement;
use App\Services\PlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Penempatan
 *
 * Penempatan talent ke klien (FR-17). Talent melihat miliknya, HR Client melihat penempatan di kliennya, internal semua.
 */
class PlacementController extends Controller
{
    public function __construct(private readonly PlacementService $placement) {}

    /**
     * Daftar penempatan.
     */
    public function index(IndexPlacementRequest $request): AnonymousResourceCollection
    {
        return PlacementResource::collection($this->placement->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Buat penempatan.
     *
     * Khusus Talent Support, dari evaluasi diterima (R18). Talent menjadi ditempatkan, pool tidak tersedia, dan job terpenuhi jika kebutuhan tercapai (R19).
     */
    public function store(StorePlacementRequest $request): JsonResponse
    {
        return (new PlacementResource($this->placement->buat($request->validated())))
            ->additional(['message' => 'Penempatan berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail penempatan.
     *
     * Beserta ringkasan jumlah cuti, shift, monitoring, dan laporan keluhan.
     */
    public function show(Placement $placement): PlacementResource
    {
        return (new PlacementResource($this->placement->detail($placement)))->additional(['message' => 'Berhasil']);
    }

    /**
     * Akhiri penempatan.
     *
     * Khusus Talent Support dan Manager: selesai atau dihentikan; talent kembali tersedia di pool (R20).
     */
    public function updateStatus(UpdateStatusPlacementRequest $request, Placement $placement): PlacementResource
    {
        $status = StatusPlacement::from($request->validated('status_placement'));

        return (new PlacementResource($this->placement->ubahStatus($placement, $status, $request->validated('tanggal_selesai'))))
            ->additional(['message' => $status === StatusPlacement::Selesai ? 'Penempatan ditandai selesai.' : 'Penempatan dihentikan.']);
    }
}
