<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\IndexShiftRequest;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\ShiftingTalent;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Operasional
 *
 * Jadwal shift talent (FR-15, R22). Dikelola HR Client (klien yang sama) atau Manager; talent melihat miliknya.
 */
class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shift) {}

    /**
     * Daftar shift.
     *
     * Urut tanggal; default 31 per halaman.
     */
    public function index(IndexShiftRequest $request): AnonymousResourceCollection
    {
        return ShiftResource::collection($this->shift->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Atur shift (massal).
     *
     * Satu jenis shift untuk banyak tanggal; tanggal yang sudah punya shift diperbarui.
     */
    public function store(StoreShiftRequest $request): JsonResponse
    {
        $hasil = $this->shift->simpanMassal($request->user(), $request->validated());

        $bagian = array_filter([
            $hasil['dibuat'] ? "{$hasil['dibuat']} shift dibuat" : null,
            $hasil['diperbarui'] ? "{$hasil['diperbarui']} shift diperbarui" : null,
        ]);

        return response()->json(['data' => $hasil, 'message' => ucfirst(implode(', ', $bagian)).'.'], 201);
    }

    /**
     * Ubah shift.
     */
    public function update(UpdateShiftRequest $request, ShiftingTalent $shift): ShiftResource
    {
        return (new ShiftResource($this->shift->ubah($request->user(), $shift, $request->validated())))
            ->additional(['message' => 'Shift berhasil diperbarui.']);
    }

    /**
     * Hapus shift.
     */
    public function destroy(Request $request, ShiftingTalent $shift): JsonResponse
    {
        $this->shift->hapus($request->user(), $shift);

        return response()->json(['data' => null, 'message' => 'Shift berhasil dihapus.']);
    }
}
