<?php

namespace Database\Seeders;

use App\Enums\JenisDokumen;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\JobRequirement;
use App\Models\PengajuanKandidat;
use App\Models\User;
use App\Services\DokumenService;
use App\Services\KelengkapanDokumenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * Rangkaian dokumen kerja sama untuk data demo (R34–R36) lewat Service asli:
 * Quotation (AM, ditandatangani HR Client) → Purchase Order (HR Client, ditandatangani Manager)
 * → PKS (AM, ditandatangani Manager + HR Client) → Kontrak Kerja (AM, ditandatangani talent + Manager).
 */
final class RangkaianDokumen
{
    /** HR Client penandatangan per klien demo. */
    private const HR_KLIEN = [
        'PT Maju Jaya' => 'hr',
        'PT Sinar Digital Nusantara' => 'hr03',
        'PT Nusantara Retail Indonesia' => 'hr06',
        'CV Teknologi Andalan' => 'hr05',
    ];

    private static int $nomor = 0;

    public function __construct(
        private readonly DokumenService $dokumen,
        private readonly KelengkapanDokumenService $kelengkapan,
    ) {}

    /**
     * Pastikan Quotation, PO, dan PKS permintaan selesai, lalu Kontrak Kerja kandidat selesai, di antara `$dari`
     * (evaluasi diterima) dan `$sampai` (penempatan dibuat).
     */
    public function lengkapiUntukPenempatan(PengajuanKandidat $pengajuan, Carbon $dari, Carbon $sampai): void
    {
        $job = $pengajuan->jobRequirement;
        $langkah = fn (float $porsi) => $dari->copy()->addSeconds((int) ($dari->diffInSeconds($sampai) * $porsi));

        if ($this->status($job, JenisDokumen::Quotation) !== 'selesai') {
            $this->quotationSelesai($job, $langkah(0.1));
            $this->poSelesai($job, $langkah(0.3));
            $this->pksSelesai($job, $langkah(0.5));
        }
        $this->kontrak($pengajuan, $langkah(0.7), ['talent' => true, 'manager' => true]);
    }

    public function quotation(JobRequirement $job, Carbon $waktu, string $am = 'am'): Dokumen
    {
        return Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user($am), [
            'jenis_dokumen' => 'quotation',
            'id_job_req' => $job->id_job_req,
            'nomor_dokumen' => 'QUO/'.$waktu->format('Y/m').'/'.$this->nomor(),
            'tanggal_dokumen' => $waktu->toDateString(),
            'berlaku_sampai' => $waktu->copy()->addDays(30)->toDateString(),
            'perihal' => "Penawaran penyediaan {$job->jumlah_kebutuhan} {$job->posisi}",
            'items' => [['deskripsi' => "{$job->posisi} — per bulan", 'jumlah' => $job->jumlah_kebutuhan, 'harga_satuan' => ($job->gaji_maks ?? 12_000_000) + 3_000_000]],
            'catatan' => 'Harga belum termasuk PPN 11%.',
        ]));
    }

    public function quotationSelesai(JobRequirement $job, Carbon $waktu): Dokumen
    {
        $q = $this->quotation($job, $waktu);
        $this->minta($q, [$this->hr($job)], $waktu);
        $this->putus($q, $this->hr($job), StatusTtd::Ditandatangani, $waktu->copy()->addHours(5));

        return $q;
    }

    /** Purchase Order diunggah HR Client klien (file PDF dari sistem pembelian klien). */
    public function po(JobRequirement $job, Carbon $waktu): Dokumen
    {
        $job->loadMissing('klien');
        $pdf = Pdf::loadHTML("<h2>Purchase Order</h2><p>{$job->klien->nama_perusahaan}</p><p>Pemesanan {$job->jumlah_kebutuhan} {$job->posisi} dari PT Intellisys IT Outsourcing.</p><p>Dokumen contoh untuk keperluan demo.</p>")->output();

        return Linimasa::pada($waktu, fn () => $this->dokumen->unggah($this->user($this->hr($job)), [
            'file' => UploadedFile::fake()->createWithContent('purchase-order-'.str($job->posisi)->slug().'.pdf', $pdf),
            'jenis_dokumen' => 'purchase_order',
            'id_job_req' => $job->id_job_req,
        ]));
    }

    public function poSelesai(JobRequirement $job, Carbon $waktu): Dokumen
    {
        $po = $this->po($job, $waktu);
        $this->minta($po, ['manager'], $waktu);
        $this->putus($po, 'manager', StatusTtd::Ditandatangani, $waktu->copy()->addHours(4));

        return $po;
    }

    public function pks(JobRequirement $job, Carbon $waktu): Dokumen
    {
        return Linimasa::pada($waktu, fn () => $this->dokumen->generate($this->user('am'), [
            'jenis_dokumen' => 'pks',
            'id_job_req' => $job->id_job_req,
            'nomor_dokumen' => 'PKS/'.$waktu->format('Y/m').'/'.$this->nomor(),
            'tanggal_dokumen' => $waktu->toDateString(),
            'berlaku_sampai' => $waktu->copy()->addYears(2)->toDateString(),
            'perihal' => "Perjanjian kerja sama penyediaan {$job->posisi}",
            'ketentuan' => "1. Masa kerja sama 24 bulan.\n2. Penggantian talent paling lambat 14 hari kerja.\n3. Tagihan dikirim setiap akhir bulan.",
        ]));
    }

    public function pksSelesai(JobRequirement $job, Carbon $waktu): Dokumen
    {
        $pks = $this->pks($job, $waktu);
        $this->minta($pks, ['manager', $this->hr($job)], $waktu);
        $this->putus($pks, 'manager', StatusTtd::Ditandatangani, $waktu->copy()->addHours(3));
        $this->putus($pks, $this->hr($job), StatusTtd::Ditandatangani, $waktu->copy()->addHours(6));

        return $pks;
    }

    /**
     * Kontrak Kerja kandidat diterima, ditandatangani talent & Manager.
     *
     * @param  array{talent?: bool, manager?: bool}  $tandaTangan  pihak yang sudah menandatangani
     */
    public function kontrak(PengajuanKandidat $pengajuan, Carbon $waktu, array $tandaTangan = []): Dokumen
    {
        $pengajuan->loadMissing(['talent.user', 'jobRequirement.klien']);
        $talent = $pengajuan->talent;
        $pdf = Pdf::loadHTML("<h2>Kontrak Kerja</h2><p>Talent: {$talent->nama_lengkap}</p><p>Posisi: {$pengajuan->jobRequirement->posisi}</p><p>Klien: {$pengajuan->jobRequirement->klien->nama_perusahaan}</p><p>Dokumen contoh untuk keperluan demo.</p>")->output();
        $kontrak = Linimasa::pada($waktu, fn () => $this->dokumen->unggah($this->user('am'), [
            'file' => UploadedFile::fake()->createWithContent('kontrak-kerja-'.str($talent->nama_lengkap)->slug().'.pdf', $pdf),
            'jenis_dokumen' => 'kontrak_kerja',
            'id_pengajuan' => $pengajuan->id_pengajuan,
        ]));

        $emailTalent = strstr($talent->user->email, '@', true);
        $this->minta($kontrak, [$emailTalent, 'manager'], $waktu);
        if ($tandaTangan['talent'] ?? false) {
            $this->putus($kontrak, $emailTalent, StatusTtd::Ditandatangani, $waktu->copy()->addHours(2));
        }
        if ($tandaTangan['manager'] ?? false) {
            $this->putus($kontrak, 'manager', StatusTtd::Ditandatangani, $waktu->copy()->addHours(4));
        }

        return $kontrak;
    }

    /**
     * @param  list<string>  $penandatangan  nama akun (bagian sebelum @intellisys.test)
     */
    public function minta(Dokumen $dokumen, array $penandatangan, Carbon $waktu): void
    {
        $id = collect($penandatangan)->map(fn (string $nama) => $this->user($nama)->id_user)->all();
        Linimasa::pada($waktu, fn () => $this->dokumen->aturPenandatangan($dokumen, $id));
    }

    public function putus(Dokumen $dokumen, string $penandatangan, StatusTtd $keputusan, Carbon $waktu): void
    {
        $gambar = $keputusan === StatusTtd::Ditandatangani
            ? UploadedFile::fake()->createWithContent('tanda-tangan.png', self::goresan(crc32($penandatangan)))
            : null;

        Linimasa::pada($waktu, fn () => $this->dokumen->tandaTangan($this->user($penandatangan), $dokumen->refresh(), $keputusan->value, $gambar));
    }

    public function hr(JobRequirement $job): string
    {
        $job->loadMissing('klien');

        return self::HR_KLIEN[$job->klien->nama_perusahaan];
    }

    private function status(JobRequirement $job, JenisDokumen $jenis): string
    {
        return $this->kelengkapan->langkah($jenis, $job->dokumen()->get())['status'];
    }

    private function nomor(): string
    {
        return str_pad((string) ++self::$nomor, 3, '0', STR_PAD_LEFT);
    }

    private function user(string $nama): User
    {
        return User::where('email', "{$nama}@intellisys.test")->firstOrFail();
    }

    /**
     * Goresan tanda tangan contoh: PNG transparan 240×90 (seperti hasil canvas di frontend), dibuat tanpa ekstensi GD.
     * Bentuk gelombang berbeda per penandatangan agar terlihat seperti tanda tangan yang berbeda.
     */
    public static function goresan(int $benih): string
    {
        [$lebar, $tinggi] = [240, 90];
        $piksel = array_fill(0, $lebar * $tinggi, false);
        [$f1, $f2, $fase] = [2 + $benih % 3, 5 + $benih % 4, ($benih % 628) / 100];
        for ($i = 0; $i <= 2000; $i++) {
            $t = $i / 2000;
            $x = 14 + $t * ($lebar - 28);
            $y = $tinggi / 2 + 22 * sin($t * $f1 * M_PI + $fase) * (1 - 0.4 * $t) + 9 * sin($t * $f2 * M_PI);
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    [$px, $py] = [(int) round($x) + $dx, (int) round($y) + $dy];
                    if ($px >= 0 && $px < $lebar && $py >= 0 && $py < $tinggi) {
                        $piksel[$py * $lebar + $px] = true;
                    }
                }
            }
        }

        $mentah = '';
        for ($y = 0; $y < $tinggi; $y++) {
            $mentah .= "\0";
            for ($x = 0; $x < $lebar; $x++) {
                $mentah .= $piksel[$y * $lebar + $x] ? "\x0b\x1f\x5c\xff" : "\0\0\0\0";
            }
        }
        $chunk = fn (string $jenis, string $isi) => pack('N', strlen($isi)).$jenis.$isi.pack('N', crc32($jenis.$isi));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $lebar, $tinggi, 8, 6, 0, 0, 0))
            .$chunk('IDAT', gzcompress($mentah))
            .$chunk('IEND', '');
    }
}
