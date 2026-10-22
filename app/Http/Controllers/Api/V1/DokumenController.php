<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusDokumen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dokumen\GenerateDokumenRequest;
use App\Http\Requests\Dokumen\IndexDokumenRequest;
use App\Http\Requests\Dokumen\PenandatanganRequest;
use App\Http\Requests\Dokumen\TandaTanganRequest;
use App\Http\Requests\Dokumen\UploadDokumenRequest;
use App\Http\Resources\DokumenResource;
use App\Models\Dokumen;
use App\Models\User;
use App\Services\DokumenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Dokumen
 *
 * Dokumen (FR-13): unggah, buat dari template, tentukan penandatangan, dan tanda tangan digital (R25–R28).
 * Internal melihat semua; HR Client dokumen kliennya; talent dokumen miliknya; setiap user juga melihat dokumen yang ia unggah atau harus ia tandatangani.
 */
class DokumenController extends Controller
{
    public function __construct(private readonly DokumenService $dokumen) {}

    /**
     * Daftar dokumen.
     */
    public function index(IndexDokumenRequest $request): AnonymousResourceCollection
    {
        return DokumenResource::collection($this->dokumen->daftar($request->user(), $request->validated()))
            ->additional(['message' => 'Berhasil']);
    }

    /**
     * Unggah dokumen.
     *
     * Multipart; pdf/doc/docx/png/jpg maks 5 MB (R25). Status awal draft.
     */
    public function upload(UploadDokumenRequest $request): JsonResponse
    {
        return (new DokumenResource($this->dokumen->unggah($request->user(), $request->validated())))
            ->additional(['message' => 'Dokumen berhasil diunggah.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Buat dokumen dari template.
     *
     * Khusus Account Manager: quotation, purchase order, atau PKS → PDF (R26). Status awal draft.
     */
    public function generate(GenerateDokumenRequest $request): JsonResponse
    {
        $dokumen = $this->dokumen->generate($request->user(), $request->validated());

        return (new DokumenResource($dokumen))
            ->additional(['message' => DokumenService::judul($dokumen->jenis_dokumen).' berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Unduh dokumen.
     */
    public function download(Dokumen $dokumen): StreamedResponse
    {
        return $this->dokumen->download($dokumen);
    }

    /**
     * Calon penandatangan.
     *
     * Khusus pengunggah: pegawai internal aktif, HR Client dari klien dokumen, dan talent pemilik dokumen.
     */
    public function calonPenandatangan(Dokumen $dokumen): JsonResponse
    {
        $data = $this->dokumen->calonPenandatangan($dokumen)->map(fn (User $u) => [
            ...DokumenResource::user($u),
            'keterangan' => $u->hrClient?->klien?->nama_perusahaan ?? $u->pegawai?->jabatan ?? 'Talent',
        ]);

        return response()->json(['data' => $data, 'message' => 'Berhasil']);
    }

    /**
     * Tentukan penandatangan.
     *
     * Khusus pengunggah, untuk dokumen draft; status menjadi menunggu tanda tangan (R27).
     */
    public function penandatangan(PenandatanganRequest $request, Dokumen $dokumen): DokumenResource
    {
        return (new DokumenResource($this->dokumen->aturPenandatangan($dokumen, $request->validated('id_user'))))
            ->additional(['message' => 'Penandatangan berhasil ditentukan. Dokumen menunggu tanda tangan.']);
    }

    /**
     * Tanda tangani atau tolak.
     *
     * Multipart: keputusan + gambar tanda tangan (R28).
     */
    public function tandaTangan(TandaTanganRequest $request, Dokumen $dokumen): DokumenResource
    {
        $dokumen = $this->dokumen->tandaTangan($request->user(), $dokumen, $request->validated('keputusan'), $request->file('file_tanda_tangan'));

        $pesan = match ($dokumen->status_dokumen) {
            StatusDokumen::Ditandatangani => 'Dokumen telah ditandatangani semua pihak.',
            StatusDokumen::Ditolak => 'Dokumen ditolak.',
            default => 'Tanda tangan tersimpan. Menunggu penandatangan lain.',
        };

        return (new DokumenResource($dokumen))->additional(['message' => $pesan]);
    }
}
