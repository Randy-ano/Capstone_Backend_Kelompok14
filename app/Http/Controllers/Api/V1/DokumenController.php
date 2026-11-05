<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusDokumen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dokumen\GenerateDokumenRequest;
use App\Http\Requests\Dokumen\IndexDokumenRequest;
use App\Http\Requests\Dokumen\PenandatanganRequest;
use App\Http\Requests\Dokumen\TandaTanganRequest;
use App\Http\Requests\Dokumen\TujuanDokumenRequest;
use App\Http\Requests\Dokumen\UploadDokumenRequest;
use App\Http\Resources\DokumenResource;
use App\Models\Dokumen;
use App\Models\User;
use App\Enums\JenisDokumen;
use App\Services\DokumenService;
use App\Services\KelengkapanDokumenService;
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
    public function __construct(
        private readonly DokumenService $dokumen,
        private readonly KelengkapanDokumenService $kelengkapan,
    ) {}

    /**
     * Detail dokumen.
     *
     * Sesuai scope (DokumenPolicy::view); dipakai tautan dari daftar periksa kelengkapan dokumen (R36).
     */
    public function show(Dokumen $dokumen): DokumenResource
    {
        return (new DokumenResource($dokumen->load(DokumenService::RELASI)))->additional(['message' => 'Berhasil']);
    }

    /**
     * Pilihan tujuan dokumen berurutan.
     *
     * Untuk form unggah: permintaan kebutuhan (quotation, purchase_order, pks) atau kandidat diterima (kontrak_kerja)
     * yang sudah memenuhi urutan (R34) dan belum punya dokumen jenis itu yang masih berjalan atau sudah selesai.
     * 403 jika role tidak berhak mengunggah jenis tersebut (R35).
     *
     * @response 200 {"data": [{"id_job_req": 5, "posisi": "Data Analyst", "id_klien": 2, "nama_perusahaan": "PT Sinar Digital Nusantara"}], "message": "Berhasil"}
     */
    public function tujuan(TujuanDokumenRequest $request): JsonResponse
    {
        $data = $this->kelengkapan->tujuan($request->user(), JenisDokumen::from($request->validated('jenis')));

        return response()->json(['data' => $data, 'message' => 'Berhasil']);
    }

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
     * Multipart; pdf/doc/docx/png/jpg maks 5 MB (R25). Status awal draft; unggahan talent langsung menunggu tanda tangan dengan penandatangan talent itu sendiri (R27).
     *
     * @response 201 {"data": {"id_dokumen": 1, "nama_file": "kontrak-kerja.pdf", "jenis_dokumen": "kontrak_kerja", "status_dokumen": "draft", "tanggal_unggah": "2026-10-22T14:12:20+07:00", "uploader": {"id_user": 1, "nama": "Andi Pratama", "role": "account_manager"}, "klien": {"id_klien": 1, "nama_perusahaan": "PT Maju Jaya"}, "talent": null, "id_placement": null, "penandatangan": [], "aksi": {"boleh_atur_penandatangan": true, "perlu_tanda_tangan_saya": false}}, "message": "Dokumen berhasil diunggah."}
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
     *
     * @response 201 {"data": {"id_dokumen": 1, "nama_file": "quotation-quo-2026-10-001.pdf", "jenis_dokumen": "quotation", "status_dokumen": "draft", "tanggal_unggah": "2026-10-22T14:12:20+07:00", "uploader": {"id_user": 1, "nama": "Andi Pratama", "role": "account_manager"}, "klien": {"id_klien": 1, "nama_perusahaan": "PT Maju Jaya"}, "talent": null, "id_placement": null, "penandatangan": [], "aksi": {"boleh_atur_penandatangan": true, "perlu_tanda_tangan_saya": false}}, "message": "Quotation berhasil dibuat."}
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
     *
     * Dokumen yang punya penandatangan dan berformat PDF/PNG/JPG dikirim sebagai PDF bertanda tangan
     * (blok tanda tangan di template, atau Lembar Pengesahan di akhir; gambar dijadikan halaman PDF, nama file berakhiran .pdf).
     * DOC/DOCX dan dokumen tanpa penandatangan dikirim apa adanya.
     *
     * @response 200 scenario="File dokumen" <Isi file biner, Content-Disposition: attachment>
     * @response 403 {"message": "Anda tidak memiliki akses ke dokumen ini."}
     */
    public function download(Dokumen $dokumen): StreamedResponse
    {
        return $this->dokumen->download($dokumen);
    }

    /**
     * Unduh file asli.
     *
     * File yang diunggah/dibuat apa adanya, tanpa tanda tangan. Hanya pengunggah dan tim internal.
     *
     * @response 200 scenario="File asli" <Isi file biner, Content-Disposition: attachment>
     * @response 403 {"message": "File asli hanya dapat diunduh oleh pengunggah dokumen dan tim internal."}
     */
    public function fileAsli(Dokumen $dokumen): StreamedResponse
    {
        return $this->dokumen->unduhAsli($dokumen);
    }

    /**
     * Gambar tanda tangan.
     *
     * Gambar tanda tangan seorang penandatangan yang sudah menandatangani (inline), untuk thumbnail di detail dokumen.
     *
     * @urlParam ttd integer required ID tanda tangan (`penandatangan[].id_ttd`). Example: 1
     *
     * @response 200 scenario="Gambar" <Isi gambar PNG/JPG, Content-Disposition: inline>
     * @response 404 {"message": "Gambar tanda tangan tidak ditemukan."}
     */
    public function gambarTandaTangan(Dokumen $dokumen, int $ttd): StreamedResponse
    {
        return $this->dokumen->gambarTandaTangan($dokumen, $ttd);
    }

    /**
     * Calon penandatangan.
     *
     * Khusus pengunggah (bukan talent). Sesuai jenis dokumen (R27): quotation/PO/PKS → AM, Manager, HR Client klien dokumen;
     * kontrak kerja/surat peringatan → talent terkait, AM, Manager; lainnya → AM, Manager, HR Client klien, talent terkait.
     *
     * @response 200 {"data": [{"id_user": 3, "nama": "Hendra Gunawan", "role": "manager", "keterangan": "Manager"}, {"id_user": 4, "nama": "Sari Wulandari", "role": "hr_client", "keterangan": "PT Maju Jaya"}], "message": "Berhasil"}
     * @response 403 {"message": "Hanya pengunggah dokumen yang dapat menentukan penandatangan."}
     */
    public function calonPenandatangan(Dokumen $dokumen): JsonResponse
    {
        $calon = $this->dokumen->calonPenandatangan($dokumen);
        $disarankan = $this->kelengkapan->penandatanganDisarankan($dokumen, $calon);
        $data = $calon->map(fn (User $u) => [
            ...DokumenResource::user($u),
            'keterangan' => $u->hrClient?->klien?->nama_perusahaan ?? $u->pegawai?->jabatan ?? 'Talent',
            'disarankan' => in_array($u->id_user, $disarankan, true),
        ]);

        return response()->json(['data' => $data, 'message' => 'Berhasil']);
    }

    /**
     * Tentukan penandatangan.
     *
     * Khusus pengunggah (bukan talent), untuk dokumen draft; penandatangan harus dari daftar calon. Status menjadi menunggu tanda tangan (R27).
     *
     * @response 200 {"data": {"id_dokumen": 1, "nama_file": "quotation-quo-2026-10-001.pdf", "jenis_dokumen": "quotation", "status_dokumen": "menunggu_ttd", "tanggal_unggah": "2026-10-22T14:12:20+07:00", "uploader": {"id_user": 1, "nama": "Andi Pratama", "role": "account_manager"}, "klien": {"id_klien": 1, "nama_perusahaan": "PT Maju Jaya"}, "talent": null, "id_placement": null, "penandatangan": [{"id_ttd": 1, "id_user": 3, "nama": "Hendra Gunawan", "role": "manager", "status_ttd": "menunggu", "tanggal_ttd": null}, {"id_ttd": 2, "id_user": 4, "nama": "Sari Wulandari", "role": "hr_client", "status_ttd": "menunggu", "tanggal_ttd": null}], "aksi": {"boleh_atur_penandatangan": false, "perlu_tanda_tangan_saya": false}}, "message": "Penandatangan berhasil ditentukan. Dokumen menunggu tanda tangan."}
     * @response 403 {"message": "Hanya pengunggah dokumen yang dapat menentukan penandatangan."}
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
     *
     * @response 200 {"data": {"id_dokumen": 1, "nama_file": "quotation-quo-2026-10-001.pdf", "jenis_dokumen": "quotation", "status_dokumen": "menunggu_ttd", "tanggal_unggah": "2026-10-22T14:12:20+07:00", "uploader": {"id_user": 1, "nama": "Andi Pratama", "role": "account_manager"}, "klien": {"id_klien": 1, "nama_perusahaan": "PT Maju Jaya"}, "talent": null, "id_placement": null, "penandatangan": [{"id_ttd": 1, "id_user": 3, "nama": "Hendra Gunawan", "role": "manager", "status_ttd": "ditandatangani", "tanggal_ttd": "2026-10-22T15:00:00+07:00"}, {"id_ttd": 2, "id_user": 4, "nama": "Sari Wulandari", "role": "hr_client", "status_ttd": "menunggu", "tanggal_ttd": null}], "aksi": {"boleh_atur_penandatangan": false, "perlu_tanda_tangan_saya": false}}, "message": "Tanda tangan tersimpan. Menunggu penandatangan lain."}
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
