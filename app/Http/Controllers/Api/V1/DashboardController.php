<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Dashboard
 *
 * Agregasi dashboard (FR-20, R32), dihitung dari data yang ada tanpa tabel baru.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * Dashboard Manager.
     *
     * Job per status, SLA terlambat/mendekati, kandidat per tahapan, placement aktif, keluhan terbuka, dan tren placement 12 bulan.
     */
    public function manager(): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->manager(), 'message' => 'Berhasil']);
    }

    /**
     * Ringkasan beranda.
     *
     * Kartu ringkas sesuai role user yang login, masing-masing dengan halaman tujuan.
     */
    public function ringkasan(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->dashboard->ringkasan($request->user()), 'message' => 'Berhasil']);
    }
}
