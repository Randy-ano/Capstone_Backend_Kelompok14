<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Talent\UnggahFileSertifikatRequest;
use App\Http\Resources\SertifikatTalentResource;
use App\Models\SertifikatTalent;
use App\Services\SertifikatService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Profil Talent
 */
class SertifikatTalentController extends Controller
{
    public function __construct(private readonly SertifikatService $sertifikat) {}

    /**
     * Unggah file sertifikat.
     *
     * Khusus talent pemilik sertifikat. Multipart `file` (pdf/jpg/png, maks 2 MB). Mengganti file lama jika ada.
     *
     * @response {"data": {"id_sertifikat": 1, "nama_sertifikat": "Laravel Certified Developer", "penerbit": "Laravel", "tanggal_terbit": "2025-03-15", "tanggal_kedaluwarsa": null, "file_sertifikat": "sertifikat-laravel.pdf", "jenis_file": "pdf", "url_sertifikat": null}, "message": "File sertifikat berhasil diunggah."}
     * @response 403 {"message": "Anda hanya dapat mengunggah file untuk sertifikat milik Anda sendiri."}
     */
    public function unggah(UnggahFileSertifikatRequest $request, SertifikatTalent $sertifikat): SertifikatTalentResource
    {
        return (new SertifikatTalentResource($this->sertifikat->unggah($sertifikat, $request->file('file'))))
            ->additional(['message' => 'File sertifikat berhasil diunggah.']);
    }

    /**
     * Lihat file sertifikat.
     *
     * Dikirim inline (PDF/gambar bisa langsung dipratinjau). Boleh: talent pemilik, AM, TS, Manager, dan HR Client
     * yang pernah menerima pengajuan talent tersebut.
     *
     * @response 200 scenario="File sertifikat" <Isi file biner, Content-Disposition: inline>
     * @response 403 {"message": "Anda tidak memiliki akses ke file sertifikat ini."}
     * @response 404 {"message": "File sertifikat belum diunggah."}
     */
    public function file(SertifikatTalent $sertifikat): StreamedResponse
    {
        return $this->sertifikat->file($sertifikat);
    }
}
