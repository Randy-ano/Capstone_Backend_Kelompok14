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
 * Dokumen beberapa jenis & status: draft, menunggu tanda tangan (termasuk manager@, hr@, talent@),
 * sudah ditandatangani, dan ditolak. Memakai Service asli (R25–R28).
 */
class DokumenSeeder extends Seeder
{
    /** Goresan tanda tangan contoh (PNG kecil), agar berkas tanda tangan benar-benar ada di disk. */
    private const PNG_TTD = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M/wHwAEBgIApD5fRAAAAABJRU5ErkJggg==';

    public function __construct(private readonly DokumenService $dokumen) {}

    public function run(): void
    {
        $this->pks();
        $this->kontrakDimas();
        $this->quotationDitolak();
        $this->quotationMenunggu();
        $this->poDraft();
        $this->perpanjanganBudi();
        $this->suratPeringatanDraft();
    }

    /** PKS PT Maju Jaya sudah ditandatangani semua pihak. */
    private function pks(): void
    {
        $waktu = Linimasa::bulan(11, 2);
        $pks = Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user('am'), [
            'jenis_dokumen' => 'pks',
            'id_klien' => $this->klien('PT Maju Jaya'),
            'nomor_dokumen' => 'PKS/'.$waktu->format('Y/m').'/003',
            'tanggal_dokumen' => $waktu->toDateString(),
            'berlaku_sampai' => $waktu->copy()->addYears(2)->toDateString(),
            'perihal' => 'Perjanjian kerja sama penyediaan talent IT',
            'ketentuan' => "1. Masa kerja sama 24 bulan.\n2. Penggantian talent paling lambat 14 hari kerja.\n3. Tagihan dikirim setiap akhir bulan.",
        ]));
        $this->minta($pks, ['am', 'manager', 'hr'], $waktu);
        $this->putus($pks, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addDay());
        $this->putus($pks, 'manager', StatusTtd::Ditandatangani, $waktu->copy()->addDays(2));
        $this->putus($pks, 'hr', StatusTtd::Ditandatangani, $waktu->copy()->addDays(4));
    }

    /** Kontrak kerja Dimas sudah ditandatangani. */
    private function kontrakDimas(): void
    {
        $waktu = Linimasa::bulan(8, 9);
        $kontrak = $this->unggahKontrak('am', 'Dimas Prasetyo', 'kontrak_kerja', 'kontrak-kerja-dimas-prasetyo', 'Kontrak Kerja', $waktu);
        $this->minta($kontrak, ['talent01', 'am', 'manager'], $waktu);
        $this->putus($kontrak, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addDay());
        $this->putus($kontrak, 'talent01', StatusTtd::Ditandatangani, $waktu->copy()->addDays(2));
        $this->putus($kontrak, 'manager', StatusTtd::Ditandatangani, $waktu->copy()->addDays(2));
    }

    /** Quotation PT Nusantara Retail ditolak HR klien. */
    private function quotationDitolak(): void
    {
        $waktu = Linimasa::hari(-24);
        $quotation = Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user('am02'), [
            'jenis_dokumen' => 'quotation',
            'id_klien' => $this->klien('PT Nusantara Retail Indonesia'),
            'nomor_dokumen' => 'QUO/'.$waktu->format('Y/m').'/007',
            'tanggal_dokumen' => $waktu->toDateString(),
            'berlaku_sampai' => $waktu->copy()->addDays(30)->toDateString(),
            'perihal' => 'Penawaran penyediaan 2 Fullstack Developer',
            'items' => [['deskripsi' => 'Fullstack Developer — per bulan', 'jumlah' => 2, 'harga_satuan' => 18000000]],
            'catatan' => 'Harga belum termasuk PPN 11%.',
        ]));
        $this->minta($quotation, ['hr06'], $waktu);
        $this->putus($quotation, 'hr06', StatusTtd::Ditolak, $waktu->copy()->addDays(3));
    }

    /** Quotation PT Maju Jaya: AM sudah tanda tangan, menunggu manager@ dan hr@. */
    private function quotationMenunggu(): void
    {
        $waktu = Linimasa::hari(-3);
        $quotation = Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user('am'), [
            'jenis_dokumen' => 'quotation',
            'id_klien' => $this->klien('PT Maju Jaya'),
            'nomor_dokumen' => 'QUO/'.$waktu->format('Y/m').'/012',
            'tanggal_dokumen' => $waktu->toDateString(),
            'berlaku_sampai' => $waktu->copy()->addDays(30)->toDateString(),
            'perihal' => 'Penawaran penyediaan 1 Frontend Developer',
            'items' => [
                ['deskripsi' => 'Frontend Developer (React) — per bulan', 'jumlah' => 1, 'harga_satuan' => 14000000],
                ['deskripsi' => 'Biaya rekrutmen & onboarding', 'jumlah' => 1, 'harga_satuan' => 5000000],
            ],
            'catatan' => 'Harga belum termasuk PPN 11%. Kontrak minimal 6 bulan.',
        ]));
        $this->minta($quotation, ['am', 'manager', 'hr'], $waktu);
        $this->putus($quotation, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addHours(2));
    }

    private function poDraft(): void
    {
        $waktu = Linimasa::hari(-2);
        Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user('am'), [
            'jenis_dokumen' => 'purchase_order',
            'id_klien' => $this->klien('PT Sinar Digital Nusantara'),
            'nomor_dokumen' => 'PO/'.$waktu->format('Y/m').'/014',
            'tanggal_dokumen' => $waktu->toDateString(),
            'perihal' => 'Pemesanan 1 QA Engineer',
            'items' => [['deskripsi' => 'QA Engineer — per bulan', 'jumlah' => 1, 'harga_satuan' => 11000000]],
        ]));
    }

    /** Perpanjangan kontrak Budi: AM sudah tanda tangan, menunggu talent@ dan manager@. */
    private function perpanjanganBudi(): void
    {
        $waktu = Linimasa::hari(-4);
        $kontrak = $this->unggahKontrak('am', 'Budi Santoso', 'kontrak_kerja', 'perpanjangan-kontrak-budi-santoso', 'Perpanjangan Kontrak Kerja', $waktu);
        $this->minta($kontrak, ['talent', 'am', 'manager'], $waktu);
        $this->putus($kontrak, 'am', StatusTtd::Ditandatangani, $waktu->copy()->addHours(3));
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
        $id = collect($penandatangan)->map(fn (string $nama) => $this->user($nama)->id_user)->all();
        Linimasa::pada($waktu, fn () => $this->dokumen->aturPenandatangan($dokumen, $id));
    }

    private function putus(Dokumen $dokumen, string $penandatangan, StatusTtd $keputusan, Carbon $waktu): void
    {
        $gambar = $keputusan === StatusTtd::Ditandatangani
            ? UploadedFile::fake()->createWithContent('tanda-tangan.png', base64_decode(self::PNG_TTD))
            : null;

        Linimasa::pada($waktu, fn () => $this->dokumen->tandaTangan($this->user($penandatangan), $dokumen, $keputusan->value, $gambar));
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
