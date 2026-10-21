<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Talent\SyncTalentKompetensiRequest;
use App\Http\Requests\Talent\UpdateTalentProfileRequest;
use App\Http\Resources\TalentResource;
use App\Services\TalentService;
use Illuminate\Http\Request;

class TalentController extends Controller
{
    public function __construct(
        private readonly TalentService $talentService
    ) {}

    /**
     * Menampilkan profil talent yang sedang login.
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
     * Memperbarui profil utama talent.
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
     * Menyinkronkan kompetensi talent.
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
