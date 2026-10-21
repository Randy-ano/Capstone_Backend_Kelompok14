<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\IndexInterviewRequest;
use App\Http\Requests\Interview\StoreInterviewRequest;
use App\Http\Requests\Interview\UpdateInterviewRequest;
use App\Http\Resources\InterviewResource;
use App\Models\JadwalInterview;
use App\Services\InterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Jadwal Interview
 *
 * Jadwal interview kandidat (FR-11). Dilihat sesuai scope pengajuan; diatur oleh AM atau HR Client klien pemilik job.
 */
class InterviewController extends Controller
{
    public function __construct(private readonly InterviewService $interview) {}

    /**
     * Daftar interview.
     *
     * Urut tanggal & waktu terdekat; bisa difilter rentang tanggal dan status.
     */
    public function index(IndexInterviewRequest $request): AnonymousResourceCollection
    {
        return InterviewResource::collection($this->interview->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Jadwalkan interview.
     *
     * Untuk pengajuan berstatus lanjut_interview yang belum dievaluasi (R16). Boleh beberapa ronde.
     */
    public function store(StoreInterviewRequest $request): JsonResponse
    {
        return (new InterviewResource($this->interview->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Interview berhasil dijadwalkan.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Jadwal ulang atau ubah status interview.
     *
     * Perubahan tanggal/waktu menjadikan status dijadwal_ulang. Status selesai/dibatalkan bersifat final.
     */
    public function update(UpdateInterviewRequest $request, JadwalInterview $interview): InterviewResource
    {
        return (new InterviewResource($this->interview->ubah($interview, $request->validated())))
            ->additional(['message' => 'Jadwal interview berhasil diperbarui.']);
    }
}
