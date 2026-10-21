<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Talent\GenerateTalentCvRequest;
use App\Http\Resources\TalentCvResource;
use App\Services\TalentCvService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Profil Talent & Kompetensi
 */
class TalentCvController extends Controller
{
    public function __construct(
        private readonly TalentCvService $talentCvService
    ) {}

    /**
     * Generate CV.
     *
     * Membuat CV PDF dari data profil talent yang sedang login (R6). Profil harus sudah lengkap (R4);
     * generate ulang menimpa CV sebelumnya.
     */
    public function generate(
        GenerateTalentCvRequest $request
    ): TalentCvResource {
        $talent = $this->talentCvService->generate(
            $request->user()->talent
        );

        return (new TalentCvResource($talent))->additional([
            'message' => 'CV berhasil dibuat.',
        ]);
    }

    /**
     * Unduh CV saya.
     *
     * Mengirim file PDF CV milik talent yang sedang login. 404 jika CV belum dibuat.
     */
    public function download(Request $request): StreamedResponse
    {
        return $this->talentCvService->download($request->user()->talent);
    }
}
