<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\IndexMonitoringRequest;
use App\Http\Requests\Monitoring\StoreMonitoringRequest;
use App\Http\Resources\MonitoringResource;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Monitoring kinerja talent (FR-15, FR-18, R23). Dicatat Manager, dilihat Manager & Account Manager; talent hanya melihat catatan placement miliknya.
 */
class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringService $monitoring) {}

    /**
     * Daftar monitoring.
     *
     * Talent hanya menerima catatan untuk placement miliknya.
     */
    public function index(IndexMonitoringRequest $request): AnonymousResourceCollection
    {
        return MonitoringResource::collection($this->monitoring->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Catat monitoring.
     */
    public function store(StoreMonitoringRequest $request): JsonResponse
    {
        return (new MonitoringResource($this->monitoring->catat($request->user(), $request->validated())))
            ->additional(['message' => 'Monitoring berhasil dicatat.'])
            ->response()
            ->setStatusCode(201);
    }
}
