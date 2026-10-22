<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifikasi\IndexNotifikasiRequest;
use App\Http\Resources\NotifikasiResource;
use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Notifikasi
 *
 * Notifikasi milik user yang login (FR-16). Terbaru dulu; notifikasi berjadwal tampil setelah waktunya.
 */
class NotifikasiController extends Controller
{
    public function __construct(private readonly NotifikasiService $notifikasi) {}

    /**
     * Daftar notifikasi.
     */
    public function index(IndexNotifikasiRequest $request): AnonymousResourceCollection
    {
        return NotifikasiResource::collection($this->notifikasi->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Jumlah belum dibaca.
     *
     * Untuk badge lonceng.
     */
    public function jumlahBelumDibaca(Request $request): JsonResponse
    {
        return response()->json(['data' => ['jumlah' => $this->notifikasi->jumlahBelumDibaca($request->user())], 'message' => 'Berhasil']);
    }

    /**
     * Tandai dibaca.
     */
    public function baca(Notifikasi $notifikasi): NotifikasiResource
    {
        $notifikasi->update(['status_baca' => true]);

        return (new NotifikasiResource($notifikasi))->additional(['message' => 'Notifikasi ditandai dibaca.']);
    }

    /**
     * Tandai semua dibaca.
     */
    public function bacaSemua(Request $request): JsonResponse
    {
        $jumlah = $this->notifikasi->bacaSemua($request->user());

        return response()->json(['data' => ['jumlah' => $jumlah], 'message' => "{$jumlah} notifikasi ditandai dibaca."]);
    }
}
