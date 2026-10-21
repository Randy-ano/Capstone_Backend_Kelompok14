<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TalentPool\IndexTalentPoolRequest;
use App\Http\Requests\TalentPool\UpdateTalentPoolRequest;
use App\Http\Resources\TalentPoolDetailResource;
use App\Http\Resources\TalentPoolResource;
use App\Models\TalentPool;
use App\Services\TalentCvService;
use App\Services\TalentPoolService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Talent Pool
 *
 * Talent berprofil lengkap otomatis masuk talent pool (R5). Dilihat AM, TS, dan Manager; diubah oleh TS.
 */
class TalentPoolController extends Controller
{
    public function __construct(
        private readonly TalentPoolService $talentPool,
        private readonly TalentCvService $talentCv,
    ) {}

    /**
     * Daftar talent pool.
     *
     * Bisa difilter status ketersediaan dan kompetensi, serta dicari berdasarkan nama, email, domisili, kompetensi, atau tag.
     */
    public function index(IndexTalentPoolRequest $request): AnonymousResourceCollection
    {
        return TalentPoolResource::collection($this->talentPool->daftar($request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Detail talent pool.
     *
     * Profil talent, riwayat pendidikan & pengalaman kerja (dengan lama pengalaman dalam tahun), sertifikat, dan riwayat screening.
     */
    public function show(TalentPool $talentPool): TalentPoolDetailResource
    {
        return (new TalentPoolDetailResource($this->talentPool->detail($talentPool)))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Ubah talent pool.
     *
     * Mengubah status ketersediaan dan/atau tag keahlian. Khusus Talent Support.
     */
    public function update(UpdateTalentPoolRequest $request, TalentPool $talentPool): TalentPoolDetailResource
    {
        return (new TalentPoolDetailResource($this->talentPool->ubah($talentPool, $request->validated())))
            ->additional(['message' => 'Data talent pool berhasil diperbarui.']);
    }

    /**
     * Unduh CV talent.
     *
     * Mengirim file PDF CV talent. 404 jika talent belum membuat CV.
     */
    public function downloadCv(TalentPool $talentPool): StreamedResponse
    {
        return $this->talentCv->download($talentPool->talent);
    }
}
