<?php

use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\Klien;
use App\Models\Placement;
use App\Models\TandaTanganDokumen;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(fn () => Storage::fake('local'));

/** PNG transparan asli (RGBA) seperti hasil canvas, dibuat tanpa ekstensi GD. */
function pngTtd(): UploadedFile
{
    [$lebar, $tinggi] = [40, 16];
    $mentah = '';
    for ($y = 0; $y < $tinggi; $y++) {
        $mentah .= "\0";
        for ($x = 0; $x < $lebar; $x++) {
            $mentah .= abs($y - 8 - (int) round(5 * sin($x / 4))) <= 1 ? "\x0b\x1f\x5c\xff" : "\0\0\0\0";
        }
    }
    $chunk = fn (string $jenis, string $isi) => pack('N', strlen($isi)).$jenis.$isi.pack('N', crc32($jenis.$isi));
    $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $lebar, $tinggi, 8, 6, 0, 0, 0)).$chunk('IDAT', gzcompress($mentah)).$chunk('IEND', '');

    return UploadedFile::fake()->createWithContent('tanda-tangan.png', $png);
}

function jumlahHalaman(string $pdf): int
{
    return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
}

/** Seluruh isi stream PDF (didekompresi) agar teks yang dicetak bisa diperiksa. */
function teksPdf(string $pdf): string
{
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $cocok);

    return collect($cocok[1])->map(fn (string $s) => @gzuncompress($s) ?: $s)->join("\n");
}

function unduh(TestResponse $response): string
{
    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    return $response->streamedContent();
}

function tandaTangani(Dokumen $dokumen, $user): void
{
    test()->actingAs($user)
        ->post("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditandatangani', 'file_tanda_tangan' => pngTtd()], ['Accept' => 'application/json'])
        ->assertOk();
}

describe('Dokumen buatan sistem: blok tanda tangan di template', function () {
    it('menyimpan data template dan merender tanda tangan saat diunduh (manager, HR Client)', function () {
        $am = loginSebagai(Role::AccountManager);
        $klien = Klien::factory()->create(['nama_perusahaan' => 'PT Uji Tanda Tangan']);
        $id = $this->postJson('/api/v1/dokumen/generate', [
            'jenis_dokumen' => 'quotation',
            'id_klien' => $klien->id_klien,
            'nomor_dokumen' => 'QUO/2026/11/001',
            'tanggal_dokumen' => now()->toDateString(),
            'perihal' => 'Penawaran talent',
            'items' => [['deskripsi' => 'Backend Developer', 'jumlah' => 1, 'harga_satuan' => 15000000]],
        ])->assertCreated()->json('data.id_dokumen');
        $dokumen = Dokumen::find($id);
        $asli = Storage::disk('local')->get($dokumen->file_path);
        Storage::disk('local')->assertExists(dirname($dokumen->file_path).'/template.json');

        $manager = buatUser(Role::Manager);
        $hr = HrClient::factory()->create(['id_klien' => $klien->id_klien, 'nama_hr' => 'Rina HR'])->user;
        $this->postJson("/api/v1/dokumen/{$id}/penandatangan", ['id_user' => [$manager->id_user, $hr->id_user]])->assertOk();
        tandaTangani($dokumen, $manager);

        // Manager sudah tanda tangan, HR belum.
        $pdf = unduh($this->actingAs($manager)->get("/api/v1/dokumen/{$id}/download"));
        expect($pdf)->toStartWith('%PDF')
            ->and(jumlahHalaman($pdf))->toBe(jumlahHalaman($asli))
            ->and(substr_count($pdf, '/Subtype /Image'))->toBeGreaterThanOrEqual(1)
            ->and(teksPdf($pdf))->toContain('Rina HR')->toContain('Belum ditandatangani')->toContain('Ditandatangani');

        // Setelah HR tanda tangan, kedua gambar tercetak; file asli tidak berubah.
        tandaTangani($dokumen, $hr);
        $pdf = unduh($this->actingAs($hr)->get("/api/v1/dokumen/{$id}/download"));
        expect(substr_count($pdf, '/Subtype /Image'))->toBeGreaterThanOrEqual(2)
            ->and(teksPdf($pdf))->not->toContain('Belum ditandatangani')
            ->and(Storage::disk('local')->get($dokumen->file_path))->toBe($asli);
    });
});

describe('PDF unggahan: Lembar Pengesahan di akhir', function () {
    it('menambah satu halaman berisi penandatangan, tanpa mengubah file asli (talent)', function () {
        $placement = Placement::factory()->create();
        $talent = loginTalentPlacement($placement);
        $asli = Pdf::loadHTML('<p>Halaman 1</p><div style="page-break-before: always">Halaman 2</div>')->output();

        $id = $this->post('/api/v1/dokumen/upload', [
            'file' => UploadedFile::fake()->createWithContent('surat-pernyataan.pdf', $asli),
            'jenis_dokumen' => 'lainnya',
            'id_placement' => $placement->id_placement,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id_dokumen');
        $dokumen = Dokumen::find($id);

        // Belum ditandatangani pun lembar sudah ada, dengan status "Belum ditandatangani".
        $pdf = unduh($this->get("/api/v1/dokumen/{$id}/download"));
        expect(jumlahHalaman($pdf))->toBe(3)
            ->and(teksPdf($pdf))->toContain('LEMBAR PENGESAHAN')->toContain('Belum ditandatangani');

        tandaTangani($dokumen, $talent);
        $pdf = unduh($this->get("/api/v1/dokumen/{$id}/download"));
        expect($pdf)->toStartWith('%PDF')
            ->and(jumlahHalaman($pdf))->toBe(3)
            ->and(substr_count($pdf, '/Subtype /Image'))->toBeGreaterThanOrEqual(1)
            ->and(teksPdf($pdf))->toContain($talent->talent->nama_lengkap)->toContain('Ditandatangani')->not->toContain('Belum ditandatangani')
            ->and(Storage::disk('local')->get($dokumen->file_path))->toBe($asli);
    });

    it('mengirim file asli jika belum ada penandatangan, bukan PDF, atau PDF tidak dapat dibaca', function (string $nama, string $isi, bool $denganPenandatangan) {
        $dokumen = Dokumen::factory()->create(['file_path' => "dokumen/abc/{$nama}", 'status_dokumen' => $denganPenandatangan ? StatusDokumen::MenungguTtd : StatusDokumen::Draft]);
        Storage::disk('local')->put($dokumen->file_path, $isi);
        if ($denganPenandatangan) {
            TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen]);
        }
        loginSebagai(Role::Manager);

        $response = $this->get("/api/v1/dokumen/{$dokumen->id_dokumen}/download")->assertOk()->assertDownload($nama);
        $isiUnduhan = $response->baseResponse instanceof StreamedResponse ? $response->streamedContent() : $response->getFile()->getContent();
        expect($isiUnduhan)->toBe($isi);
    })->with([
        'draft tanpa penandatangan' => ['kontrak.pdf', fn () => Pdf::loadHTML('<p>Kontrak</p>')->output(), false],
        'gambar' => ['scan.png', 'bukan-pdf', true],
        'PDF rusak' => ['rusak.pdf', '%PDF-1.4 isi tidak valid', true],
    ]);
});
