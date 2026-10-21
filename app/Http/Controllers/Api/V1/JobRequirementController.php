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
 * @group Job Requirement
 *
 * Kebutuhan talent dari klien (FR-05), rekomendasi kandidat (FR-07), dan log progres (FR-09).
 * HR Client hanya mengakses job requirement milik kliennya.
 */
class JobRequirementController extends Controller
{
    public function __construct(
        private readonly JobRequirementService $jobRequirement,
        private readonly RekomendasiService $rekomendasi,
        private readonly ProgresService $progres,
    ) {}

    /**
     * Daftar job requirement.
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
     * Buat job requirement.
     *
     * HR Client: klien otomatis miliknya. Account Manager: wajib memilih klien dan menjadi AM penanggung jawab.
     */
    public function store(StoreJobRequirementRequest $request): JsonResponse
    {
        return (new JobRequirementResource($this->jobRequirement->buat($request->user(), $request->validated())))
            ->additional(['message' => 'Job requirement berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Detail job requirement.
     *
     * Beserta kompetensi dan ringkasan progres (jumlah screening per hasil, terpenuhi, sisa kebutuhan).
     */
    public function show(JobRequirement $jobRequirement): JobRequirementResource
    {
        return (new JobRequirementResource($this->jobRequirement->detail($jobRequirement)))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Ubah job requirement.
     *
     * Hanya saat berstatus draft atau diajukan. `kompetensi`, jika dikirim, menggantikan daftar lama.
     */
    public function update(UpdateJobRequirementRequest $request, JobRequirement $jobRequirement): JobRequirementResource
    {
        return (new JobRequirementResource($this->jobRequirement->ubah($jobRequirement, $request->validated())))
            ->additional(['message' => 'Job requirement berhasil diperbarui.']);
    }

    /**
     * Ajukan atau batalkan job requirement.
     *
     * `diajukan` hanya dari draft (mencatat progres "kebutuhan_dibuat"); `dibatalkan` dari draft/diajukan/diproses dan mengembalikan talent yang sedang diproses menjadi tersedia.
     */
    public function updateStatus(UpdateStatusJobRequirementRequest $request, JobRequirement $jobRequirement): JobRequirementResource
    {
        $status = StatusReq::from($request->validated('status_req'));

        return (new JobRequirementResource($this->jobRequirement->ubahStatus($jobRequirement, $status)))
            ->additional(['message' => $status === StatusReq::Diajukan
                ? 'Job requirement berhasil diajukan.'
                : 'Job requirement berhasil dibatalkan.']);
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
            ->additional(['message' => "{$kandidat->count()} kandidat direkomendasikan"]);
    }

    /**
     * Log progres job requirement.
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
