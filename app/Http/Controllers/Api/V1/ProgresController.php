<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequirement\IndexJobRequirementRequest;
use App\Http\Resources\JobRequirementResource;
use App\Services\ProgresService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Progres
 *
 * Monitoring progres pemenuhan semua job requirement (FR-09). Khusus internal.
 */
class ProgresController extends Controller
{
    public function __construct(private readonly ProgresService $progres) {}

    /**
     * Monitoring progres.
     *
     * Semua job requirement dengan indikator SLA terkini, log progres terakhir, jumlah screening, dan terpenuhi. Urut deadline terdekat.
     */
    public function index(IndexJobRequirementRequest $request): AnonymousResourceCollection
    {
        return JobRequirementResource::collection($this->progres->monitoring($request->validated()))
            ->additional(['message' => 'Berhasil']);
    }
}
