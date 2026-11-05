<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusReq;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequirement\IndexJobRequirementRequest;
use App\Http\Requests\JobRequirement\StoreJobRequirementRequest;
use App\Http\Requests\JobRequirement\UpdateJobRequirementRequest;
use App\Http\Requests\JobRequirement\UpdateStatusJobRequirementRequest;
use App\Http\Resources\JobRequirementResource;
use App\Http\Resources\RekomendasiResource;
use App\Models\JobRequirement;
use App\Services\JobRequirementService;
use App\Services\ProgresService;
use App\Services\RekomendasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Permintaan Kebutuhan
 *
 * Kebutuhan talent dari klien (FR-05), rekomendasi kandidat (FR-07), dan log progres (FR-09).
 * HR Client hanya mengakses permintaan kebutuhan milik kliennya.
 */
class JobRequirementController extends Controller
{
    public function __construct(
        private readonly JobRequirementService $jobRequirement,
        private readonly RekomendasiService $rekomendasi,
        private readonly ProgresService $progres,
    ) {}

    /**
     * Daftar permintaan kebutuhan.
     *
     * `meta.ringkasan` berisi jumlah per status dan per indikator SLA (terlambat/mendekati) dari semua job yang boleh dilihat, tidak terpengaruh filter.
     */
    public function index(IndexJobRequirementRequest $request): AnonymousResourceCollection
    {
        return JobRequirementResource::collection($this->jobRequirement->daftar($request->user(), $request->validated()))
            ->additional([
                'message' => 'Berhasil',
                'meta' => ['ringkasan' => $this->jobRequirement->ringkasan($request->user())],
            ]);
    }

    /**
     * Buat permintaan kebutuhan.
     *
     * HR Client: klien otomatis miliknya. Account Manager: wajib memilih klien dan menjadi AM penanggung jawab.
     */
    public function store(StoreJobRequirementRequest $request): JsonResponse
    {
        return (new JobRequirementResource($this->jobRequirement->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Permintaan kebutuhan berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail permintaan kebutuhan.
     *
     * Beserta kompetensi dan ringkasan progres (jumlah screening per hasil, terpenuhi, sisa kebutuhan).
     */
    public function show(JobRequirement $jobRequirement): JobRequirementResource
    {
        return (new JobRequirementResource($this->jobRequirement->detail($jobRequirement)))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Ubah permintaan kebutuhan.
     *
     * Hanya saat berstatus draft atau diajukan. `kompetensi`, jika dikirim, menggantikan daftar lama.
     */
    public function update(UpdateJobRequirementRequest $request, JobRequirement $jobRequirement): JobRequirementResource
    {
        return (new JobRequirementResource($this->jobRequirement->ubah($jobRequirement, $request->validated())))
            ->additional(['message' => 'Permintaan kebutuhan berhasil diperbarui.']);
    }

    /**
     * Ajukan atau batalkan permintaan kebutuhan.
     *
     * `diajukan` hanya dari draft (mencatat progres "kebutuhan_dibuat"); `dibatalkan` dari draft/diajukan/diproses dan mengembalikan talent yang sedang diproses menjadi tersedia.
     */
    public function updateStatus(UpdateStatusJobRequirementRequest $request, JobRequirement $jobRequirement): JobRequirementResource
    {
        $status = StatusReq::from($request->validated('status_req'));

        return (new JobRequirementResource($this->jobRequirement->ubahStatus($jobRequirement, $status, $request->user())))
            ->additional(['message' => $status === StatusReq::Diajukan
                ? 'Permintaan kebutuhan berhasil diajukan.'
                : 'Permintaan kebutuhan berhasil dibatalkan.']);
    }

    /**
     * Rekomendasi kandidat.
     *
     * Pencocokan berbasis aturan (R9): skor = 0,8 × skor kompetensi + 0,2 × skor pengalaman. Kandidat: talent pool tersedia, belum di-screening untuk job ini, cocok minimal 1 kompetensi. Khusus Talent Support; job harus berstatus diajukan/diproses.
     */
    public function rekomendasi(JobRequirement $jobRequirement): AnonymousResourceCollection
    {
        $kandidat = $this->rekomendasi->daftar($jobRequirement);

        return RekomendasiResource::collection($kandidat)
            ->additional([
                'message' => "{$kandidat->count()} kandidat direkomendasikan",
                'meta' => ['skor_minimum' => RekomendasiService::SKOR_MINIMUM],
            ]);
    }

    /**
     * Log progres permintaan kebutuhan.
     *
     * Indikator SLA terkini dan seluruh log progres (terbaru dulu).
     */
    public function progres(JobRequirement $jobRequirement): JobRequirementResource
    {
        $job = $this->jobRequirement->detail($jobRequirement)
            ->setRelation('progres', $this->progres->riwayat($jobRequirement));

        return (new JobRequirementResource($job))->additional(['message' => 'Berhasil']);
    }
}
