<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Dompdf\Frame;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

/**
 * PDF bertanda tangan yang dibuat saat dokumen diunduh (file asli di disk tidak diubah).
 *
 * Teks dirender dompdf dari Blade. Gambar tanda tangan ditempel dengan FPDI/FPDF pada kotak ber-id "ttd-…" yang
 * posisinya dicatat saat dompdf merender. Cara ini membuat PNG transparan dari canvas bisa dipakai tanpa ekstensi GD,
 * yang diperlukan dompdf untuk gambar PNG.
 */
class PdfTandaTanganService
{
    private const PREFIX_KOTAK = 'ttd-';

    /**
     * Dokumen buatan sistem: render ulang template dengan blok tanda tangan per penandatangan.
     *
     * @param  array<string, mixed>  $dataView
     */
    public function template(string $view, array $dataView, Dokumen $dokumen): string
    {
        $penandatangan = $this->penandatangan($dokumen);
        [$pdf, $kotak] = $this->render($view, [...$dataView, 'penandatangan' => $penandatangan]);

        return $this->susun([[$pdf, $kotak]], $this->gambar($penandatangan));
    }

    /**
     * PDF unggahan: halaman asli ditambah halaman "Lembar Pengesahan" di akhir.
     * Jika PDF asli tidak dapat dibaca FPDI (mis. memakai kompresi cross-reference), file asli dikirim apa adanya.
     */
    public function lembarPengesahan(string $pdfAsli, Dokumen $dokumen): string
    {
        try {
            return $this->denganLembar($pdfAsli, $dokumen);
        } catch (Throwable $e) {
            Log::warning("Lembar pengesahan dokumen {$dokumen->id_dokumen} tidak dapat ditambahkan: {$e->getMessage()}");

            return $pdfAsli;
        }
    }

    /**
     * Gambar unggahan (PNG/JPG): halaman pertama berisi gambar dimuat utuh & proporsional di A4
     * (lanskap bila gambar lebih lebar daripada tinggi), lalu Lembar Pengesahan.
     * Null jika gambar tidak dapat dibaca FPDF (mis. PNG interlaced/16-bit); pemanggil mengirim file asli.
     */
    public function gambarDenganPengesahan(string $pathGambar, Dokumen $dokumen): ?string
    {
        try {
            return $this->denganLembar($this->pdfGambar($pathGambar), $dokumen);
        } catch (Throwable $e) {
            Log::warning("Dokumen gambar {$dokumen->id_dokumen} tidak dapat dijadikan PDF: {$e->getMessage()}");

            return null;
        }
    }

    /** PDF asli + Lembar Pengesahan dengan gambar tanda tangan. */
    private function denganLembar(string $pdfAsli, Dokumen $dokumen): string
    {
        $penandatangan = $this->penandatangan($dokumen);
        [$lembar, $kotak] = $this->render('pdf.lembar-pengesahan', [
            'judul' => DokumenService::judul($dokumen->jenis_dokumen),
            'namaFile' => DokumenService::namaFile($dokumen),
            'dokumen' => $dokumen,
            'penandatangan' => $penandatangan,
        ]);

        return $this->susun([[$pdfAsli, []], [$lembar, $kotak]], $this->gambar($penandatangan));
    }

    /** Satu halaman A4 (pt) berisi gambar, diperkecil agar muat dengan margin 36 pt; tidak diperbesar melebihi 1 pt/piksel. */
    private function pdfGambar(string $path): string
    {
        $info = @getimagesize($path);
        if (! $info || $info[0] <= 0 || $info[1] <= 0 || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new \RuntimeException('File bukan gambar PNG/JPG yang valid.');
        }

        $lanskap = $info[0] > $info[1];
        [$lebarHalaman, $tinggiHalaman] = $lanskap ? [841.89, 595.28] : [595.28, 841.89];
        $margin = 36.0;
        $skala = min(($lebarHalaman - 2 * $margin) / $info[0], ($tinggiHalaman - 2 * $margin) / $info[1], 1.0);
        [$w, $h] = [$info[0] * $skala, $info[1] * $skala];

        $fpdf = new Fpdi($lanskap ? 'L' : 'P', 'pt', 'A4');
        $fpdf->SetAutoPageBreak(false);
        $fpdf->AddPage();
        $fpdf->Image($path, ($lebarHalaman - $w) / 2, ($tinggiHalaman - $h) / 2, $w, $h, $info[2] === IMAGETYPE_PNG ? 'PNG' : 'JPG');

        return $fpdf->Output('S');
    }

    /**
     * Data penandatangan untuk Blade: urut sesuai penunjukan.
     *
     * @return list<array{kotak: string, nama: string, peran: string, status: StatusTtd, label_status: string, waktu: string|null, gambar: string|null}>
     */
    public function penandatangan(Dokumen $dokumen): array
    {
        return $dokumen->tandaTangan()
            ->with(['penandatangan.talent', 'penandatangan.hrClient.klien', 'penandatangan.pegawai'])
            ->orderBy('id_ttd')
            ->get()
            ->map(fn (TandaTanganDokumen $ttd) => [
                'kotak' => self::PREFIX_KOTAK.$ttd->id_ttd,
                'nama' => $ttd->penandatangan->namaTampilan(),
                'peran' => self::peran($ttd->penandatangan),
                'status' => $ttd->status_ttd,
                'label_status' => match ($ttd->status_ttd) {
                    StatusTtd::Ditandatangani => 'Ditandatangani',
                    StatusTtd::Ditolak => 'Ditolak',
                    StatusTtd::Menunggu => 'Belum ditandatangani',
                },
                'waktu' => $ttd->tanggal_ttd?->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i').($ttd->tanggal_ttd ? ' WIB' : ''),
                'gambar' => $ttd->status_ttd === StatusTtd::Ditandatangani && $ttd->file_tanda_tangan
                    && Storage::disk(DokumenService::DISK)->exists($ttd->file_tanda_tangan) ? Storage::disk(DokumenService::DISK)->path($ttd->file_tanda_tangan)
                        : null,
            ])
            ->values()
            ->all();
    }

    /** Peran/jabatan penandatangan untuk dicetak di PDF. */
    public static function peran(User $user): string
    {
        return match ($user->role) {
            Role::Talent => 'Talent',
            Role::HrClient => collect([$user->hrClient?->jabatan ?: 'HR Client', $user->hrClient?->klien?->nama_perusahaan])->filter()->join(', '),
            Role::AccountManager => $user->pegawai?->jabatan ?: 'Account Manager',
            Role::TalentSupport => $user->pegawai?->jabatan ?: 'Talent Support',
            Role::Manager => $user->pegawai?->jabatan ?: 'Manager',
        };
    }

    /**
     * Render Blade ke PDF sambil mencatat posisi kotak tanda tangan (satuan pt, titik asal kiri atas).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: array<string, array{halaman: int, x: float, y: float, w: float, h: float}>}
     */
    private function render(string $view, array $data): array
    {
        $kotak = [];
        $pdf = Pdf::loadView($view, $data);
        $pdf->getDomPDF()->setCallbacks([[
            'event' => 'end_frame',
            'f' => function (Frame $frame, Canvas $canvas) use (&$kotak) {
                $node = $frame->get_node();
                if (! $node instanceof \DOMElement || ! str_starts_with($node->getAttribute('id'), self::PREFIX_KOTAK)) {
                    return;
                }
                $box = $frame->get_padding_box();
                $kotak[$node->getAttribute('id')] = [
                    'halaman' => $canvas->get_page_number(),
                    'x' => (float) $box['x'],
                    'y' => (float) $box['y'],
                    'w' => (float) $box['w'],
                    'h' => (float) $box['h'],
                ];
            },
        ]]);

        return [$pdf->output(), $kotak];
    }

    /**
     * Gabungkan beberapa PDF lalu tempel gambar tanda tangan pada kotaknya.
     *
     * @param  list<array{0: string, 1: array<string, array{halaman: int, x: float, y: float, w: float, h: float}>}>  $bagian
     * @param  array<string, string>  $gambar  id kotak → path gambar
     */
    private function susun(array $bagian, array $gambar): string
    {
        $fpdi = new Fpdi('P', 'pt');
        $fpdi->SetAutoPageBreak(false);

        foreach ($bagian as [$isi, $kotak]) {
            $jumlah = $fpdi->setSourceFile(StreamReader::createByString($isi));
            for ($halaman = 1; $halaman <= $jumlah; $halaman++) {
                $tpl = $fpdi->importPage($halaman);
                $ukuran = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($ukuran['orientation'], [$ukuran['width'], $ukuran['height']]);
                $fpdi->useTemplate($tpl);

                foreach ($kotak as $id => $k) {
                    if ($k['halaman'] === $halaman && isset($gambar[$id])) {
                        $this->tempel($fpdi, $gambar[$id], $k);
                    }
                }
            }
        }

        return $fpdi->Output('S');
    }

    /**
     * Gambar dimuat proporsional di tengah kotak. Gambar yang tidak didukung FPDF (mis. PNG interlaced) dilewati;
     * status "Ditandatangani" tetap tercetak.
     *
     * @param  array{halaman: int, x: float, y: float, w: float, h: float}  $k
     */
    private function tempel(Fpdi $fpdi, string $path, array $k): void
    {
        try {
            $info = @getimagesize($path);
            if (! $info || $info[0] <= 0 || $info[1] <= 0) {
                return;
            }
            $jarak = 2.0;
            $skala = min(($k['w'] - 2 * $jarak) / $info[0], ($k['h'] - 2 * $jarak) / $info[1]);
            [$w, $h] = [$info[0] * $skala, $info[1] * $skala];
            $jenis = $info[2] === IMAGETYPE_PNG ? 'PNG' : 'JPG';
            $fpdi->Image($path, $k['x'] + ($k['w'] - $w) / 2, $k['y'] + ($k['h'] - $h) / 2, $w, $h, $jenis);
        } catch (Throwable $e) {
            Log::warning("Gambar tanda tangan {$path} tidak dapat ditempel: {$e->getMessage()}");
        }
    }

    /**
     * @param  list<array{kotak: string, gambar: string|null}>  $penandatangan
     * @return array<string, string>
     */
    private function gambar(array $penandatangan): array
    {
        return collect($penandatangan)->filter(fn (array $p) => $p['gambar'] !== null)->pluck('gambar', 'kotak')->all();
    }
}
