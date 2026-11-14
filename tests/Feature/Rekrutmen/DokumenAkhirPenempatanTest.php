<?php

use App\Enums\JenisDokumen;
use App\Enums\KanalNotifikasi;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusPlacement;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\Notifikasi;
use App\Models\Placement;
use App\Models\User;
use App\Services\DokumenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Surat akhir penempatan (feedback SUS 2026-11-14): Selesai → Surat Keterangan Selesai Penempatan,
 * Dihentikan → Surat Pemberhentian Penempatan (dengan alasan). Ditandatangani Manager lewat mekanisme R27–R28a.
 */
beforeEach(fn () => Storage::fake('local'));

/** Penempatan aktif 1 Jan – 31 Des 2026 + HR Client kliennya. */
function penempatanAktif(): array
{
    $p = Placement::factory()->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-12-31', 'posisi_penempatan' => 'Backend Developer']);
    $p->talent->update(['nama_lengkap' => 'Budi Santoso']);
    $p->klien->update(['nama_perusahaan' => 'PT Maju Jaya']);
    $hr = HrClient::factory()->create(['id_klien' => $p->id_klien]);

    return [$p, $hr->user];
}

function akhiri(Placement $p, string $status, array $tambahan = [])
{
    return test()->patchJson("/api/v1/placement/{$p->id_placement}/status", ['status_placement' => $status, 'tanggal_selesai' => '2026-11-14', ...$tambahan]);
}

function suratDari(Placement $p): Dokumen
{
    return Dokumen::where('id_placement', $p->id_placement)->whereIn('jenis_dokumen', JenisDokumen::AKHIR_PENEMPATAN)->sole();
}

/** Teks yang dicetak di PDF (stream didekompresi). */
function teksSuratPdf(string $pdf): string
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

/** PNG RGBA kecil tanpa ekstensi GD. */
function pngTtdManager(): UploadedFile
{
    [$lebar, $tinggi] = [24, 10];
    $mentah = str_repeat("\0".str_repeat("\x0b\x1f\x5c\xff", $lebar), $tinggi);
    $chunk = fn (string $jenis, string $isi) => pack('N', strlen($isi)).$jenis.$isi.pack('N', crc32($jenis.$isi));
    $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $lebar, $tinggi, 8, 6, 0, 0, 0)).$chunk('IDAT', gzcompress($mentah)).$chunk('IEND', '');

    return UploadedFile::fake()->createWithContent('tanda-tangan.png', $png);
}

/** Surat Pemberhentian untuk penempatan yang dihentikan Manager. */
function suratPemberhentian(): array
{
    $manager = loginSebagai(Role::Manager);
    [$p, $hr] = penempatanAktif();
    akhiri($p, 'dihentikan', ['alasan_penghentian' => 'Proyek dihentikan klien.'])->assertOk();

    return [suratDari($p), $p, $hr, $manager];
}

function notifTipe(User $user): array
{
    return Notifikasi::where('id_user_penerima', $user->id_user)->pluck('tipe_notifikasi')->all();
}

describe('PATCH /placement/{id}/status membuat surat akhir penempatan', function () {
    it('Selesai oleh TS → Surat Keterangan Selesai Penempatan menunggu tanda tangan Manager aktif pertama', function () {
        $manager = buatUser(Role::Manager);
        $managerLain = buatUser(Role::Manager);
        loginSebagai(Role::TalentSupport);
        [$p] = penempatanAktif();

        akhiri($p, 'selesai')
            ->assertOk()
            ->assertJsonPath('message', 'Penempatan ditandai selesai. Surat Keterangan Selesai Penempatan dibuat dan menunggu tanda tangan Manager.')
            ->assertJsonPath('data.status_placement', 'selesai')
            // TS tidak berhak melihat surat akhir penempatan, jadi tidak muncul di historinya.
            ->assertJsonPath('data.dokumen', [])
            ->assertJsonPath('data.alasan_penghentian', null);

        $surat = suratDari($p);
        expect($surat->jenis_dokumen)->toBe(JenisDokumen::SuratSelesaiPenempatan)
            ->and($surat->status_dokumen)->toBe(StatusDokumen::MenungguTtd)
            ->and([$surat->id_talent, $surat->id_klien, $surat->id_job_req])->toBe([$p->id_talent, $p->id_klien, $p->id_job_req])
            ->and($surat->tandaTangan->pluck('id_user_penandatangan')->all())->toBe([$manager->id_user])
            ->and(Storage::disk('local')->get($surat->file_path))->toStartWith('%PDF')
            ->and(notifTipe($p->talent->user))->toBe(['surat_akhir_penempatan'])
            ->and(notifTipe($manager))->toBe(['tanda_tangan_diminta'])
            ->and(notifTipe($managerLain))->toBe([]);

        $template = json_decode(Storage::disk('local')->get(DokumenService::pathTemplate($surat->file_path)), true);
        expect($template['data']['nomor_dokumen'])->toBe('001/SKSP/ITS/'.['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1].'/'.now()->year)
            ->and($template['alasan'])->toBeNull();

        $teks = teksSuratPdf(Storage::disk('local')->get($surat->file_path));
        expect($teks)->toContain('PT Intellisys IT Outsourcing')->toContain('SURAT KETERANGAN SELESAI PENEMPATAN')
            ->toContain('Budi Santoso')->toContain('Backend Developer')->toContain('PT Maju Jaya')
            ->toContain('1 Januari 2026')->toContain('31 Desember 2026')->toContain('14 November 2026')
            ->toContain('Belum ditandatangani');

        $notif = Notifikasi::where('id_user_penerima', $p->talent->id_user)->sole();
        expect($notif->kanal)->toBe(KanalNotifikasi::Sistem)
            ->and($notif->pesan)->toBe('Surat Keterangan Selesai Penempatan untuk penempatan Anda sebagai Backend Developer di PT Maju Jaya telah dibuat dan menunggu tanda tangan Manager. Dokumen dapat dilihat di menu Dokumen.');
    });

    it('Dihentikan oleh Manager → Surat Pemberhentian Penempatan berisi alasan, ditandatangani Manager pelaku', function () {
        $managerLama = buatUser(Role::Manager);
        $manager = loginSebagai(Role::Manager);
        [$p] = penempatanAktif();

        akhiri($p, 'dihentikan', ['alasan_penghentian' => 'Klien mengakhiri proyek lebih awal.'])
            ->assertOk()
            ->assertJsonPath('message', 'Penempatan dihentikan. Surat Pemberhentian Penempatan dibuat dan menunggu tanda tangan Manager.')
            ->assertJsonPath('data.dokumen.0.jenis_dokumen', 'surat_pemberhentian_penempatan')
            ->assertJsonPath('data.dokumen.0.label_jenis', 'Surat Pemberhentian Penempatan')
            ->assertJsonPath('data.dokumen.0.status_dokumen', 'menunggu_ttd')
            ->assertJsonPath('data.alasan_penghentian', 'Klien mengakhiri proyek lebih awal.');

        $surat = suratDari($p);
        expect($surat->tandaTangan->pluck('id_user_penandatangan')->all())->toBe([$manager->id_user])
            ->and(notifTipe($managerLama))->toBe([]);
        $teks = teksSuratPdf(Storage::disk('local')->get($surat->file_path));
        expect($teks)->toContain('SURAT PEMBERHENTIAN PENEMPATAN')->toContain('Alasan penghentian')->toContain('Klien mengakhiri proyek lebih awal.')
            ->toContain('Dihentikan per tanggal')->toContain('/SPHP/ITS/');
    });

    it('nomor surat berurutan per jenis dalam satu tahun', function () {
        buatUser(Role::Manager);
        loginSebagai(Role::TalentSupport);
        [$p1] = penempatanAktif();
        [$p2] = penempatanAktif();
        [$p3] = penempatanAktif();

        akhiri($p1, 'selesai')->assertOk();
        akhiri($p2, 'selesai')->assertOk();
        akhiri($p3, 'dihentikan', ['alasan_penghentian' => 'Kinerja tidak sesuai.'])->assertOk();

        $nomor = fn (Placement $p) => json_decode(Storage::disk('local')->get(DokumenService::pathTemplate(suratDari($p)->file_path)), true)['data']['nomor_dokumen'];
        expect($nomor($p1))->toStartWith('001/SKSP/')
            ->and($nomor($p2))->toStartWith('002/SKSP/')
            ->and($nomor($p3))->toStartWith('001/SPHP/');
    });

    it('tanpa Manager aktif surat tetap dibuat sebagai draft tanpa penandatangan', function () {
        loginSebagai(Role::TalentSupport);
        [$p] = penempatanAktif();

        akhiri($p, 'selesai')->assertOk();

        expect(suratDari($p)->status_dokumen)->toBe(StatusDokumen::Draft)
            ->and(suratDari($p)->tandaTangan)->toHaveCount(0)
            ->and(notifTipe($p->talent->user))->toBe(['surat_akhir_penempatan']);
    });

    it('notifikasi talent memakai kanal email bila mailer aktif', function () {
        Queue::fake();
        config(['mail.default' => 'brevo']);
        buatUser(Role::Manager);
        loginSebagai(Role::TalentSupport);
        [$p] = penempatanAktif();

        akhiri($p, 'selesai')->assertOk();

        expect(Notifikasi::where('id_user_penerima', $p->talent->id_user)->sole()->kanal)->toBe(KanalNotifikasi::Email);
    });

    it('alasan penghentian wajib (maks. 1000) dan penempatan tidak berubah bila gagal', function (array $body, string $pesan) {
        loginSebagai(Role::Manager);
        [$p] = penempatanAktif();

        akhiri($p, 'dihentikan', $body)
            ->assertUnprocessable()
            ->assertJsonPath('errors.alasan_penghentian.0', $pesan);

        expect($p->fresh()->status_placement)->toBe(StatusPlacement::Aktif)
            ->and(Dokumen::count())->toBe(0);
    })->with([
        'kosong' => [[], 'Alasan penghentian wajib diisi bila penempatan dihentikan.'],
        'spasi saja' => [['alasan_penghentian' => '   '], 'Alasan penghentian wajib diisi bila penempatan dihentikan.'],
        'terlalu panjang' => [['alasan_penghentian' => str_repeat('a', 1001)], 'Alasan penghentian maksimal 1000 karakter.'],
    ]);

    it('jenis surat akhir penempatan tidak bisa diunggah manual (422)', function (string $jenis) {
        loginSebagai(Role::AccountManager);
        [$p] = penempatanAktif();

        $this->post('/api/v1/dokumen/upload', [
            'file' => UploadedFile::fake()->create('surat.pdf', 20, 'application/pdf'),
            'jenis_dokumen' => $jenis,
            'id_placement' => $p->id_placement,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis_dokumen']);
    })->with(['surat_selesai_penempatan', 'surat_pemberhentian_penempatan']);
});

describe('Akses surat akhir penempatan', function () {
    it('bisa diunduh talent bersangkutan, AM, Manager, dan HR Client kliennya', function () {
        [$surat, $p, $hr, $manager] = suratPemberhentian();
        $am = buatUser(Role::AccountManager);

        foreach ([$p->talent->user, $am, $manager, $hr] as $user) {
            Sanctum::actingAs($user);
            $this->get("/api/v1/dokumen/{$surat->id_dokumen}/download")->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}")->assertOk()->assertJsonPath('data.jenis_dokumen', 'surat_pemberhentian_penempatan');
            $this->getJson('/api/v1/dokumen?jenis=surat_pemberhentian_penempatan')->assertOk()->assertJsonPath('data.0.id_dokumen', $surat->id_dokumen);
        }
    });

    it('talent tetap bisa membuka menu Dokumen setelah penempatan berakhir walau tanpa Kontrak Kerja', function () {
        [$surat, $p] = suratPemberhentian();
        Sanctum::actingAs($p->talent->user);

        expect($p->talent->fresh()->sudahDitempatkan())->toBeFalse();
        $this->getJson('/api/v1/dokumen')->assertOk()->assertJsonPath('data.0.id_dokumen', $surat->id_dokumen);
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.akses_dokumen', true);
    });

    it('Talent Support ditolak walaupun dialah yang mengakhiri penempatan', function () {
        buatUser(Role::Manager);
        $ts = loginSebagai(Role::TalentSupport);
        [$p] = penempatanAktif();
        akhiri($p, 'selesai')->assertOk();
        $surat = suratDari($p);
        expect($surat->id_user_uploader)->toBe($ts->id_user);

        $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}")->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke dokumen ini.']);
        $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}/download")->assertForbidden();
        $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}/file-asli")->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke dokumen ini.']);
        $this->getJson('/api/v1/dokumen?jenis=surat_selesai_penempatan')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/placement/{$p->id_placement}")->assertOk()->assertJsonPath('data.dokumen', []);
    });

    it('HR Client klien lain dan talent lain ditolak', function () {
        [$surat] = suratPemberhentian();

        Sanctum::actingAs(HrClient::factory()->create()->user);
        $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}/download")->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke dokumen ini.']);

        $lain = Placement::factory()->create();
        Sanctum::actingAs($lain->talent->user);
        $this->getJson("/api/v1/dokumen/{$surat->id_dokumen}/download")->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke dokumen ini.']);
    });

    it('DokumenPolicy::view per role', function () {
        [$surat, $p, $hr, $manager] = suratPemberhentian();
        $boleh = fn (User $u) => Gate::forUser($u)->allows('view', $surat);

        expect($boleh($p->talent->user))->toBeTrue()
            ->and($boleh(buatUser(Role::AccountManager)))->toBeTrue()
            ->and($boleh($manager))->toBeTrue()
            ->and($boleh($hr))->toBeTrue()
            ->and($boleh(buatUser(Role::TalentSupport)))->toBeFalse()
            ->and($boleh(HrClient::factory()->create()->user))->toBeFalse()
            ->and($boleh(buatUser(Role::Talent)))->toBeFalse();
    });

    it('histori dokumen di detail penempatan untuk HR Client kliennya memuat surat & alasan', function () {
        [$surat, $p, $hr] = suratPemberhentian();
        Sanctum::actingAs($hr);

        $this->getJson("/api/v1/placement/{$p->id_placement}")
            ->assertOk()
            ->assertJsonPath('data.dokumen.0.id_dokumen', $surat->id_dokumen)
            ->assertJsonPath('data.alasan_penghentian', 'Proyek dihentikan klien.');
    });
});

describe('Tanda tangan Manager', function () {
    it('setelah Manager menandatangani, surat ditandatangani, talent dinotifikasi, dan PDF memuat tanda tangannya', function () {
        $manager = buatUser(Role::Manager);
        loginSebagai(Role::TalentSupport);
        [$p] = penempatanAktif();
        akhiri($p, 'selesai')->assertOk();
        $surat = suratDari($p);

        $this->actingAs($manager)
            ->post("/api/v1/dokumen/{$surat->id_dokumen}/tanda-tangan", ['keputusan' => 'ditandatangani', 'file_tanda_tangan' => pngTtdManager()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status_dokumen', 'ditandatangani');

        expect($surat->tandaTangan()->sole()->status_ttd)->toBe(StatusTtd::Ditandatangani)
            ->and(notifTipe($p->talent->user))->toBe(['surat_akhir_penempatan', 'surat_akhir_penempatan'])
            ->and(Notifikasi::where('id_user_penerima', $p->talent->id_user)->latest('id_notifikasi')->first()->pesan)
            ->toContain('telah ditandatangani Manager dan dapat diunduh');

        Sanctum::actingAs($p->talent->user);
        $pdf = $this->get("/api/v1/dokumen/{$surat->id_dokumen}/download")->assertOk()->streamedContent();
        expect(teksSuratPdf($pdf))->toContain('Ditandatangani')->not->toContain('Belum ditandatangani');
    });

    it('calon penandatangan surat akhir penempatan hanya Manager', function () {
        buatUser(Role::Manager);
        buatUser(Role::AccountManager);
        $ts = loginSebagai(Role::TalentSupport);
        [$p, $hr] = penempatanAktif();
        akhiri($p, 'selesai')->assertOk();

        $calon = app(DokumenService::class)->calonPenandatangan(suratDari($p));
        expect($calon->pluck('role')->unique()->values()->all())->toBe([Role::Manager]);
    });
});
