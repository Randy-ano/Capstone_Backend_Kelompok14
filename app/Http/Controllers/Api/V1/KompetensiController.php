<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kompetensi\IndexKompetensiRequest;
use App\Http\Resources\KompetensiResource;
use App\Services\KompetensiService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Profil Talent & Kompetensi
 */
class KompetensiController extends Controller
{
    public function __construct(private readonly KompetensiService $kompetensi) {}

    /**
     * Daftar kompetensi.
     *
     * Master kompetensi untuk pilihan di profil talent, job requirement, dan filter talent pool. Semua role.
     */
    public function index(IndexKompetensiRequest $request): AnonymousResourceCollection
    {
        return KompetensiResource::collection($this->kompetensi->daftar($request->validated()))
            ->additional(['message' => 'Berhasil']);
    }
}
