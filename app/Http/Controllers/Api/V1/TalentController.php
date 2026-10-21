<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Talent\SyncTalentKompetensiRequest;
use App\Http\Requests\Talent\UpdateTalentProfileRequest;
use App\Http\Resources\TalentResource;
use App\Services\TalentService;
use Illuminate\Http\Request;

/**
 * @group Profil Talent & Kompetensi
 *
 * Profil milik talent yang sedang login. Khusus role talent.
 */
class TalentController extends Controller
{
    public function __construct(
        private readonly TalentService $talentService
    ) {}

    /**
     * Profil saya.
     *
     * Profil talent yang sedang login beserta kompetensi, riwayat pendidikan, pengalaman kerja, sertifikat, dan bahasa.
     */
    public function showProfile(Request $request): TalentResource
    {
        $talent = $this->talentService->getProfile(
            $request->user()
        );

        return (new TalentResource($talent))->additional([
            'message' => 'Profil talent berhasil diambil.',
        ]);
    }

    /**
     * Ubah profil.
     *
     * Semua kolom opsional. Status kelengkapan dihitung ulang (R4) dan talent otomatis masuk talent pool saat lengkap (R5).
     */
    public function updateProfile(
        UpdateTalentProfileRequest $request
    ): TalentResource {
        $talent = $this->talentService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return (new TalentResource($talent))->additional([
            'message' => 'Profil talent berhasil diperbarui.',
        ]);
    }

    /**
     * Atur kompetensi.
     *
     * Mengganti seluruh daftar kompetensi talent; status kelengkapan dihitung ulang (R4, R5).
     */
    public function syncKompetensi(
        SyncTalentKompetensiRequest $request
    ): TalentResource {
        $talent = $this->talentService->syncKompetensi(
            $request->user(),
            $request->validated('id_kompetensi')
        );

        return (new TalentResource($talent))->additional([
            'message' => 'Kompetensi talent berhasil diperbarui.',
        ]);
    }
}
