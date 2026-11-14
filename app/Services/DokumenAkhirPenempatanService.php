<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusPlacement;
use App\Models\Dokumen;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Surat akhir penempatan (feedback SUS 2026-11-14): saat penempatan diakhiri, sistem membuat
 * "Surat Keterangan Selesai Penempatan" (Selesai) atau "Surat Pemberhentian Penempatan" (Dihentikan, memuat alasan).
 *
 * PDF dibuat dengan dompdf (view pdf.surat-akhir-penempatan). Data surat disimpan di template.json satu folder dengan
 * PDF-nya, sama seperti dokumen buatan template (R26), sehingga saat diunduh PDF dirender ulang dengan tanda tangan
 * Manager (R28a). Alasan penghentian hanya disimpan di template.json (tanpa kolom baru).
 */
class DokumenAkhirPenempatanService
{
    private const KODE = [
        'surat_selesai_penempatan' => 'SKSP',
        'surat_pemberhentian_penempatan' => 'SPHP',
    ];

    private const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function __construct(private readonly PdfTandaTanganService $pdfTtd) {}

    public static function jenis(StatusPlacement $status): JenisDokumen
    {
        return $status === StatusPlacement::Dihentikan ? JenisDokumen::SuratPemberhentianPenempatan : JenisDokumen::SuratSelesaiPenempatan;
    }

    /**
     * Buat surat untuk penempatan yang baru diakhiri. Penandatangan: Manager pelaku, atau Manager aktif pertama bila
     * diakhiri Talent Support; dokumen langsung menunggu tanda tangan. Tanpa Manager aktif dokumen tetap draft.
     *
     * @param  string|null  $selesaiKontrak  tanggal selesai kontrak sebelum diakhiri (periode penempatan)
     */
    public function buat(Placement $placement, User $pelaku, ?string $selesaiKontrak, ?string $alasan): Dokumen
    {
        $placement->loadMissing(['talent.user', 'klien']);
        $jenis = self::jenis($placement->status_placement);
        $nomor = $this->nomorBerikutnya($jenis);

        $template = [
            'judul' => DokumenService::judul($jenis),
            'jenis' => $jenis->value,
            'data' => [
                'nomor_dokumen' => $nomor,
                'tanggal_dokumen' => now()->toDateString(),
            ],
            'klien' => $placement->klien->only(['nama_perusahaan', 'alamat', 'penanggung_jawab']),
            'talent' => ['nama_lengkap' => $placement->talent->nama_lengkap],
            'posisi' => $placement->posisi_penempatan,
            'tanggal_mulai' => $placement->tanggal_mulai->toDateString(),
            'tanggal_selesai_kontrak' => $selesaiKontrak,
            'tanggal_berakhir' => $placement->tanggal_selesai->toDateString(),
            'alasan' => $jenis === JenisDokumen::SuratPemberhentianPenempatan ? $alasan : null,
            'pembuat' => $pelaku->namaTampilan(),
        ];

        $path = 'dokumen/'.Str::uuid().'/'.Str::slug($jenis->value.' '.$placement->talent->nama_lengkap.' '.str_replace('/', '-', $nomor)).'.pdf';
        $dokumen = Dokumen::create([
            'id_user_uploader' => $pelaku->id_user,
            'id_klien' => $placement->id_klien,
            'id_talent' => $placement->id_talent,
            'id_placement' => $placement->id_placement,
            'id_job_req' => $placement->id_job_req,
            'jenis_dokumen' => $jenis,
            'file_path' => $path,
            'tanggal_unggah' => now(),
        ]);

        $manager = $this->penandatangan($pelaku);
        if ($manager !== null) {
            $dokumen->tandaTangan()->create(['id_user_penandatangan' => $manager->id_user]);
            $dokumen->update(['status_dokumen' => StatusDokumen::MenungguTtd]);
        }

        Storage::disk(DokumenService::DISK)->put(DokumenService::pathTemplate($path), json_encode($template, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        Storage::disk(DokumenService::DISK)->put($path, $this->pdfTtd->template(DokumenService::viewTemplate($jenis), DokumenService::dataView($template), $dokumen));

        return $dokumen->load(DokumenService::RELASI);
    }

    /** Alasan penghentian dari data surat pemberhentian (null untuk jenis lain / data tidak ada). */
    public static function alasan(Dokumen $dokumen): ?string
    {
        if ($dokumen->jenis_dokumen !== JenisDokumen::SuratPemberhentianPenempatan) {
            return null;
        }
        $path = DokumenService::pathTemplate($dokumen->file_path);
        if (! Storage::disk(DokumenService::DISK)->exists($path)) {
            return null;
        }

        return json_decode(Storage::disk(DokumenService::DISK)->get($path), true)['alasan'] ?? null;
    }

    private function penandatangan(User $pelaku): ?User
    {
        if ($pelaku->hasRole(Role::Manager) && $pelaku->status_aktif) {
            return $pelaku;
        }

        return NotifikasiService::managerAktif()->sortBy('id_user')->first();
    }

    /** Nomor urut per jenis per tahun: 001/SKSP/ITS/XI/2026. */
    private function nomorBerikutnya(JenisDokumen $jenis): string
    {
        $kini = Carbon::now();
        $urut = Dokumen::where('jenis_dokumen', $jenis)->whereYear('tanggal_unggah', $kini->year)->count() + 1;

        return sprintf('%03d/%s/ITS/%s/%d', $urut, self::KODE[$jenis->value], self::ROMAWI[$kini->month], $kini->year);
    }
}
