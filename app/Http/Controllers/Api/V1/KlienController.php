<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Klien\IndexKlienRequest;
use App\Http\Resources\KlienResource;
use App\Services\KlienService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Klien
 *
 * Daftar klien (read-only) untuk pilihan klien saat Account Manager membuat job requirement. Khusus internal.
 */
class KlienController extends Controller
{
    public function __construct(private readonly KlienService $klien) {}

    /**
     * Daftar klien.
     */
    public function index(IndexKlienRequest $request): AnonymousResourceCollection
    {
        return KlienResource::collection($this->klien->daftar($request->validated()))
            ->additional(['message' => 'Berhasil']);
    }
}
