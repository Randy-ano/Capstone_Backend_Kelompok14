<?php

namespace Database\Seeders;

use App\Enums\StatusPlacement;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\Klien;
use App\Models\Placement;
use App\Models\User;
use App\Services\DokumenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * Dokumen di luar rangkaian rekrutmen (RangkaianDokumen dipanggil RekrutmenSeeder): perpanjangan kontrak menunggu
 * talent@ & manager@, berita acara menunggu hr@, dan surat peringatan draft. Memakai Service asli (R25–R28).
 */
class DokumenSeeder extends Seeder
{
    public function __construct(
        private readonly DokumenService $dokumen,
        private readonly RangkaianDokumen $rangkaian,
    ) {}

    public function run(): void
    {
        $this->perpanjanganBudi();
        $this->beritaAcaraMenungguHr();
        $this->suratPeringatanDraft();
    }

    /** Perpanjangan kontrak Budi: AM sudah tanda tangan, menunggu talent@ dan manager@. */
    private function perpanjanganBudi(): void
    {
        $waktu = Linimasa::hari(-4);
        $kontrak = $this->unggahKontrak('am', 'Budi Santoso', 'kontrak_kerja', 'perpanjangan-kontrak-budi-santoso', 'Perpanjangan Kontrak Kerja', $waktu);
        $this->minta($kontrak, ['talent', 'am', 'manager'], $waktu);
        $this->putus($kontrak, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addHours(3));
    }

    /** Berita acara serah terima talent PT Maju Jaya: AM sudah tanda tangan, menunggu hr@. */
    private function beritaAcaraMenungguHr(): void
    {
        $waktu = Linimasa::hari(-3);
        $pdf = Pdf::loadHTML('<h2>Berita Acara Serah Terima Talent</h2><p>PT Maju Jaya</p><p>Dokumen contoh untuk keperluan demo.</p>')->output();
        $dokumen = Linimasa::pada($waktu, fn () => $this->dokumen->unggah($this->user('am'), [
            'file' => UploadedFile::fake()->createWithContent('berita-acara-serah-terima-talent.pdf', $pdf),
            'jenis_dokumen' => 'lainnya',
            'id_klien' => $this->klien('PT Maju Jaya'),
        ]));
        $this->minta($dokumen, ['am', 'hr'], $waktu);
        $this->putus($dokumen, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addHours(2));
    }

    private function suratPeringatanDraft(): void
    {
        $this->unggahKontrak('manager', 'Yoga Pratama', 'surat_peringatan', 'surat-peringatan-yoga-pratama', 'Surat Peringatan', Linimasa::hari(-1));
    }

    /** Dokumen kepegawaian diunggah lewat alur unggah biasa, berisi PDF ringkas. */
    private function unggahKontrak(string $pengunggah, string $talent, string $jenis, string $namaFile, string $judul, Carbon $waktu): Dokumen
    {
        $placement = Placement::where('status_placement', StatusPlacement::Aktif)
            ->whereHas('talent', fn ($q) => $q->where('nama_lengkap', $talent))
            ->with('klien')
            ->firstOrFail();
        $pdf = Pdf::loadHTML("<h2>{$judul}</h2><p>Talent: {$talent}</p><p>Posisi: {$placement->posisi_penempatan}</p><p>Klien: {$placement->klien->nama_perusahaan}</p><p>Dokumen contoh untuk keperluan demo.</p>")->output();

        return Linimasa::pada($waktu, fn () => $this->dokumen->unggah($this->user($pengunggah), [
            'file' => UploadedFile::fake()->createWithContent("{$namaFile}.pdf", $pdf),
            'jenis_dokumen' => $jenis,
            'id_placement' => $placement->id_placement,
        ]));
    }

    /**
     * @param  list<string>  $penandatangan
     */
    private function minta(Dokumen $dokumen, array $penandatangan, Carbon $waktu): void
    {
        $this->rangkaian->minta($dokumen, $penandatangan, $waktu);
    }

    private function putus(Dokumen $dokumen, string $penandatangan, StatusTtd $keputusan, Carbon $waktu): void
    {
        $this->rangkaian->putus($dokumen, $penandatangan, $keputusan, $waktu);
    }

    private function klien(string $nama): int
    {
        return Klien::where('nama_perusahaan', $nama)->value('id_klien');
    }

    private function user(string $nama): User
    {
        return User::where('email', "{$nama}@intellisys.test")->firstOrFail();
    }
}
