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

/**
 * Seluruh isi stream PDF (didekompresi) agar teks yang dicetak bisa diperiksa.
 * Akhir baris sebelum "endstream" dicoba beberapa kemungkinan, karena data terkompresi bisa berakhir dengan byte \r.
 */
function teksPdf(string $pdf): string
{
    preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $cocok);

    return collect($cocok[1])->map(function (string $s) {
        foreach ([substr($s, 0, -1), substr($s, 0, -2), $s] as $calon) {
            if (($isi = @gzuncompress($calon)) !== false) {
                return $isi;
            }
        }

        return $s;
    })->join("\n");
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
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($klien->id_klien), App\Enums\StatusPengajuan::LanjutInterview);
        rekrutmenEvaluasi($pengajuan);
        $id = $this->postJson('/api/v1/dokumen/generate', [
            'jenis_dokumen' => 'quotation',
            'id_job_req' => $pengajuan->id_job_req,
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
        'gambar tidak valid' => ['scan.png', 'bukan-gambar', true],
        'DOCX' => ['kontrak.docx', 'isi-docx', true],
        'PDF rusak' => ['rusak.pdf', '%PDF-1.4 isi tidak valid', true],
    ]);
});

/** PNG RGB polos (gambar dokumen hasil pindai), dibuat tanpa ekstensi GD. */
function pngDokumen(int $lebar, int $tinggi): string
{
    $mentah = str_repeat("\0".str_repeat("\xf4\xf1\xe8", $lebar), $tinggi);
    $chunk = fn (string $jenis, string $isi) => pack('N', strlen($isi)).$jenis.$isi.pack('N', crc32($jenis.$isi));

    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $lebar, $tinggi, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($mentah)).$chunk('IEND', '');
}

/** JPEG 60×40 piksel (lanskap). */
function jpgDokumen(): string
{
    return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAoADwDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD0KiiioKCiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA//Z');
}

/** Ukuran halaman ke-n PDF (pt). */
function ukuranHalaman(string $pdf, int $halaman = 1): array
{
    $fpdi = new Fpdi('P', 'pt');
    $fpdi->setSourceFile(StreamReader::createByString($pdf));

    return $fpdi->getTemplateSize($fpdi->importPage($halaman));
}

/** Talent (yang sedang login) mengunggah dokumen ke penempatannya. */
function unggahTalent(Placement $placement, string $nama, string $isi): Dokumen
{
    $id = test()->post('/api/v1/dokumen/upload', [
        'file' => UploadedFile::fake()->createWithContent($nama, $isi),
        'jenis_dokumen' => 'lainnya',
        'id_placement' => $placement->id_placement,
    ], ['Accept' => 'application/json'])->assertCreated()->json('data.id_dokumen');

    return Dokumen::find($id);
}

describe('Gambar unggahan (PNG/JPG): dijadikan PDF + Lembar Pengesahan', function () {
    it('talent mengunggah PNG, menandatangani, lalu mengunduh PDF berisi gambar + lembar pengesahan', function () {
        $placement = Placement::factory()->create();
        $talent = loginTalentPlacement($placement);
        $png = pngDokumen(300, 200);
        $dokumen = unggahTalent($placement, 'Surat Keterangan.png', $png);

        $this->getJson('/api/v1/dokumen')->assertOk()
            ->assertJsonPath('data.0.unduhan.nama_file', 'surat-keterangan.pdf')
            ->assertJsonPath('data.0.unduhan.diolah', true)
            ->assertJsonPath('data.0.unduhan.dengan_tanda_tangan', false)
            ->assertJsonPath('data.0.penandatangan.0.ada_gambar_ttd', false)
            ->assertJsonPath('data.0.aksi.boleh_unduh_asli', true);

        tandaTangani($dokumen, $talent);
        $this->getJson('/api/v1/dokumen')
            ->assertJsonPath('data.0.unduhan.dengan_tanda_tangan', true)
            ->assertJsonPath('data.0.penandatangan.0.ada_gambar_ttd', true);

        $response = $this->get("/api/v1/dokumen/{$dokumen->id_dokumen}/download");
        $response->assertDownload('surat-keterangan.pdf');
        $pdf = unduh($response);
        $halaman1 = ukuranHalaman($pdf);
        expect($pdf)->toStartWith('%PDF')
            ->and(jumlahHalaman($pdf))->toBe(2)
            ->and($halaman1['orientation'])->toBe('L')
            ->and(round($halaman1['width']))->toBe(842.0)
            ->and(substr_count($pdf, '/Subtype /Image'))->toBeGreaterThanOrEqual(2)
            ->and(teksPdf($pdf))->toContain('LEMBAR PENGESAHAN')->toContain($talent->talent->nama_lengkap)->toContain('Ditandatangani')
            ->and(Storage::disk('local')->get($dokumen->file_path))->toBe($png);

        // File asli tetap bisa diunduh pengunggah, apa adanya.
        $asli = $this->get("/api/v1/dokumen/{$dokumen->id_dokumen}/file-asli")->assertOk()->assertDownload('surat-keterangan.png');
        expect($asli->streamedContent())->toBe($png);
    });

    it('JPG lanskap dan PNG potret dimuat di halaman A4 sesuai orientasinya', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);
        $jpg = unggahTalent($placement, 'ktp.jpg', jpgDokumen());
        $potret = unggahTalent($placement, 'ijazah.png', pngDokumen(120, 400));

        $pdf = unduh($this->get("/api/v1/dokumen/{$jpg->id_dokumen}/download"));
        expect(jumlahHalaman($pdf))->toBe(2)->and(ukuranHalaman($pdf)['orientation'])->toBe('L')
            ->and(teksPdf($pdf))->toContain('Belum ditandatangani');

        $pdf = unduh($this->get("/api/v1/dokumen/{$potret->id_dokumen}/download"));
        expect(jumlahHalaman($pdf))->toBe(2)->and(ukuranHalaman($pdf)['orientation'])->toBe('P');
    });

    it('DOCX bertanda tangan dikirim apa adanya; resource menandai tidak diolah', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);
        $dokumen = unggahTalent($placement, 'kontrak.docx', 'isi-docx');

        $this->getJson('/api/v1/dokumen')->assertJsonPath('data.0.unduhan.nama_file', 'kontrak.docx')
            ->assertJsonPath('data.0.unduhan.diolah', false);
        $this->get("/api/v1/dokumen/{$dokumen->id_dokumen}/download")->assertOk()->assertDownload('kontrak.docx');
    });
});

describe('File asli & gambar tanda tangan', function () {
    it('file asli hanya untuk pengunggah dan internal', function () {
        $placement = Placement::factory()->create();
        $talent = loginTalentPlacement($placement);
        $dokumen = unggahTalent($placement, 'surat.png', pngDokumen(20, 20));

        foreach ([Role::AccountManager, Role::TalentSupport, Role::Manager] as $role) {
            $this->actingAs(buatUser($role))->get("/api/v1/dokumen/{$dokumen->id_dokumen}/file-asli")->assertOk();
        }

        // HR Client klien penempatan boleh mengunduh dokumen bertanda tangan, tetapi tidak file aslinya.
        $hr = HrClient::factory()->create(['id_klien' => $placement->id_klien])->user;
        $this->actingAs($hr)->get("/api/v1/dokumen/{$dokumen->id_dokumen}/download")->assertOk();
        $this->actingAs($hr)->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/file-asli")->assertForbidden()
            ->assertJsonPath('message', 'File asli hanya dapat diunduh oleh pengunggah dokumen dan tim internal.');
        $this->actingAs($hr)->getJson('/api/v1/dokumen')->assertJsonPath('data.0.aksi.boleh_unduh_asli', false);

        $this->actingAs($talent)->get("/api/v1/dokumen/{$dokumen->id_dokumen}/file-asli")->assertOk();
    });

    it('gambar tanda tangan bisa dilihat oleh yang berhak melihat dokumen', function () {
        $placement = Placement::factory()->create();
        $talent = loginTalentPlacement($placement);
        $dokumen = unggahTalent($placement, 'surat.pdf', Pdf::loadHTML('<p>Surat</p>')->output());
        $ttd = $dokumen->tandaTangan()->first();
        $url = "/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan/{$ttd->id_ttd}/gambar";

        // Belum menandatangani → 404.
        $this->getJson($url)->assertNotFound()->assertJsonPath('message', 'Gambar tanda tangan tidak ditemukan.');

        tandaTangani($dokumen, $talent);
        $isi = $this->actingAs($talent)->get($url)->assertOk()->assertHeader('content-type', 'image/png');
        expect($isi->headers->get('content-disposition'))->toStartWith('inline')
            ->and($isi->streamedContent())->toStartWith("\x89PNG");

        $this->actingAs(buatUser(Role::Manager))->get($url)->assertOk();
        $this->actingAs(HrClient::factory()->create(['id_klien' => $placement->id_klien])->user)->get($url)->assertOk();
        $this->actingAs(buatUser(Role::HrClient))->getJson($url)->assertForbidden();

        // id_ttd milik dokumen lain → 404.
        $lain = TandaTanganDokumen::factory()->create(['file_tanda_tangan' => $ttd->fresh()->file_tanda_tangan, 'status_ttd' => 'ditandatangani']);
        $this->actingAs(buatUser(Role::Manager))->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan/{$lain->id_ttd}/gambar")->assertNotFound();
    });
});
