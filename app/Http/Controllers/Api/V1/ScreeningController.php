<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Screening\IndexScreeningRequest;
use App\Http\Requests\Screening\StoreScreeningRequest;
use App\Http\Requests\Screening\TidakDiteruskanRequest;
use App\Http\Requests\Screening\UpdateScreeningRequest;
use App\Http\Resources\ScreeningResource;
use App\Models\ScreeningTalent;
use App\Services\ScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Screening
 *
 * Usulan kandidat (FR-08, revisi 2026-11-05). Dilihat TS, AM, Manager; diusulkan oleh Talent Support ke Account Manager,
 * lalu AM meneruskan ke klien (POST /pengajuan) atau tidak meneruskan.
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
     * Usulkan kandidat ke Account Manager.
     *
     * Hasil langsung "lolos" (usulan). Skor kecocokan dihitung otomatis sebagai informasi (R9, tanpa batas minimum).
     * Talent menjadi "dalam_proses", job "diajukan" menjadi "diproses" (R10), dan AM penanggung jawab dinotifikasi.
     */
    public function store(StoreScreeningRequest $request): JsonResponse
    {
        return (new ScreeningResource($this->screening->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Kandidat berhasil diusulkan ke Account Manager.'])
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
            ->additional(['message' => 'Usulan berhasil diperbarui.']);
    }

    /**
     * Tidak teruskan usulan ke klien.
     *
     * Khusus Account Manager. Hasil menjadi "tidak_lolos" (final), alasan tersimpan, talent kembali tersedia (R11),
     * dan Talent Support pengusul dinotifikasi.
     */
    public function tidakDiteruskan(TidakDiteruskanRequest $request, ScreeningTalent $screening): ScreeningResource
    {
        return (new ScreeningResource($this->screening->tidakDiteruskan($screening, $request->validated('alasan'))))
            ->additional(['message' => 'Usulan tidak diteruskan ke klien.']);
    }
}
