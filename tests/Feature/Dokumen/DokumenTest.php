<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\Klien;
use App\Models\Placement;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

// Tanpa ekstensi GD: file palsu bertipe image/png sudah cukup karena backend hanya menyimpan gambar.
function gambarTtd(): UploadedFile
{
    return UploadedFile::fake()->create('ttd.png', 20, 'image/png');
}

function dataGenerate(array $ubah = []): array
{
    return [
        'jenis_dokumen' => 'quotation',
        'id_klien' => Klien::factory()->create()->id_klien,
        'nomor_dokumen' => 'QUO/2026/10/001',
        'tanggal_dokumen' => now()->toDateString(),
        'perihal' => 'Penawaran 2 Backend Developer',
        'items' => [['deskripsi' => 'Backend Developer (per bulan)', 'jumlah' => 2, 'harga_satuan' => 15000000]],
        ...$ubah,
    ];
}

describe('POST /dokumen/upload (R25)', function () {
    it('menyimpan file di disk private dengan nama asli dan status draft', function () {
        $am = loginSebagai(Role::AccountManager);
        $klien = Klien::factory()->create();

        $respons = $this->post('/api/v1/dokumen/upload', [
            'file' => UploadedFile::fake()->create('Kontrak Kerja Budi.pdf', 300, 'application/pdf'),
            'jenis_dokumen' => 'kontrak_kerja',
            'id_klien' => $klien->id_klien,
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('message', 'Dokumen berhasil diunggah.')
            ->assertJsonPath('data.nama_file', 'kontrak-kerja-budi.pdf')
            ->assertJsonPath('data.status_dokumen', 'draft')
            ->assertJsonPath('data.uploader.id_user', $am->id_user)
            ->assertJsonPath('data.klien.id_klien', $klien->id_klien)
            ->assertJsonPath('data.aksi', ['boleh_atur_penandatangan' => true, 'perlu_tanda_tangan_saya' => false]);

        Storage::disk('local')->assertExists(Dokumen::sole()->file_path);
        expect(Dokumen::sole()->file_path)->toStartWith('dokumen/');
    });

    it('placement menentukan talent & klien; HR Client dan talent selalu untuk dirinya', function () {
        $placement = Placement::factory()->create();
        loginHrKlien($placement);
        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('sp.pdf', 10), 'jenis_dokumen' => 'surat_peringatan', 'id_placement' => $placement->id_placement], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.klien.id_klien', $placement->id_klien)
            ->assertJsonPath('data.talent.id_talent', $placement->id_talent);

        // HR Client tanpa placement: klien miliknya, id_klien kiriman diabaikan.
        $hr = loginSebagai(Role::HrClient);
        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('po.pdf', 10), 'jenis_dokumen' => 'purchase_order', 'id_klien' => $placement->id_klien], ['Accept' => 'application/json'])
            ->assertJsonPath('data.klien.id_klien', $hr->idKlien());

        $talent = loginTalentPlacement($placement);
        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('ijazah.png', 50, 'image/png'), 'jenis_dokumen' => 'lainnya'], ['Accept' => 'application/json'])
            ->assertJsonPath('data.talent.id_talent', $talent->talent->id_talent)
            ->assertJsonPath('data.klien', null);
    });

    it('menolak placement milik pihak lain (403)', function () {
        $placement = Placement::factory()->create();
        loginSebagai(Role::HrClient);

        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('a.pdf', 10), 'jenis_dokumen' => 'lainnya', 'id_placement' => $placement->id_placement], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke placement ini.']);
    });

    it('menolak file tidak valid (422)', function (Closure $file, string $jenis, array $error) {
        loginSebagai(Role::Manager);

        $this->post('/api/v1/dokumen/upload', ['file' => $file(), 'jenis_dokumen' => $jenis], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($error);
    })->with([
        'tipe exe' => [fn () => UploadedFile::fake()->create('virus.exe', 10), 'lainnya', ['file' => 'File harus berformat PDF, DOC, DOCX, PNG, atau JPG.']],
        'lebih dari 5 MB' => [fn () => UploadedFile::fake()->create('besar.pdf', 5121, 'application/pdf'), 'lainnya', ['file' => 'Ukuran file maksimal 5 MB.']],
        'jenis cv' => [fn () => UploadedFile::fake()->create('cv.pdf', 10), 'cv', ['jenis_dokumen']],
        'tanpa file' => [fn () => null, 'lainnya', ['file']],
    ]);

    it('menolak Talent Support (403)', function () {
        loginSebagai(Role::TalentSupport);

        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('a.pdf', 10), 'jenis_dokumen' => 'lainnya'], ['Accept' => 'application/json'])
            ->assertForbidden();
    });
});

describe('POST /dokumen/generate (R26)', function () {
    it('AM membuat quotation PDF dari template', function () {
        $am = loginSebagai(Role::AccountManager);
        $data = dataGenerate();

        $this->postJson('/api/v1/dokumen/generate', $data)
            ->assertCreated()
            ->assertJsonPath('message', 'Quotation berhasil dibuat.')
            ->assertJsonPath('data.jenis_dokumen', 'quotation')
            ->assertJsonPath('data.nama_file', 'quotation-quo-2026-10-001.pdf')
            ->assertJsonPath('data.klien.id_klien', $data['id_klien'])
            ->assertJsonPath('data.uploader.nama', $am->pegawai->nama_pegawai);

        $isi = Storage::disk('local')->get(Dokumen::sole()->file_path);
        expect($isi)->toStartWith('%PDF');
    });

    it('PKS memakai ketentuan, tanpa item', function () {
        loginSebagai(Role::AccountManager);

        $this->postJson('/api/v1/dokumen/generate', dataGenerate(['jenis_dokumen' => 'pks', 'items' => null, 'ketentuan' => "1. Masa kerja 12 bulan.\n2. Evaluasi tiap 3 bulan."]))
            ->assertCreated()
            ->assertJsonPath('message', 'Perjanjian Kerja Sama berhasil dibuat.');
    });

    it('menolak data tidak valid (422)', function (array $ubah, array $error) {
        loginSebagai(Role::AccountManager);

        $this->postJson('/api/v1/dokumen/generate', dataGenerate($ubah))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($error);
    })->with([
        'jenis lain' => [['jenis_dokumen' => 'kontrak_kerja'], ['jenis_dokumen']],
        'quotation tanpa item' => [['items' => []], ['items' => 'Tambahkan minimal 1 item.']],
        'item tidak lengkap' => [['items' => [['deskripsi' => '', 'jumlah' => 0]]], ['items.0.deskripsi', 'items.0.jumlah', 'items.0.harga_satuan']],
        'pks tanpa ketentuan' => [['jenis_dokumen' => 'pks'], ['ketentuan' => 'Ketentuan wajib diisi untuk Perjanjian Kerja Sama.']],
        'berlaku sebelum tanggal' => [['berlaku_sampai' => now()->subDay()->toDateString()], ['berlaku_sampai']],
        'tanpa klien & nomor' => [['id_klien' => null, 'nomor_dokumen' => ''], ['id_klien', 'nomor_dokumen']],
    ]);

    it('menolak selain AM (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/dokumen/generate', dataGenerate())->assertForbidden();
    })->with(roleSelain(Role::AccountManager));
});

describe('GET /dokumen & download (scope)', function () {
    it('internal melihat semua; HR Client dokumen kliennya; talent dokumen miliknya; penandatangan melihat dokumennya', function () {
        $placement = Placement::factory()->create();
        Dokumen::factory()->create(['id_klien' => $placement->id_klien]);
        Dokumen::factory()->create(['id_talent' => $placement->id_talent, 'id_klien' => $placement->id_klien, 'id_placement' => $placement->id_placement]);
        $lain = Dokumen::factory()->create(['id_klien' => Klien::factory()]);
        $diminta = Dokumen::factory()->create(['status_dokumen' => StatusDokumen::MenungguTtd]);

        loginSebagai(Role::TalentSupport);
        $this->getJson('/api/v1/dokumen')->assertOk()->assertJsonCount(4, 'data');

        $hr = loginHrKlien($placement);
        $this->getJson('/api/v1/dokumen')->assertJsonCount(2, 'data');
        $this->get("/api/v1/dokumen/{$lain->id_dokumen}/download")->assertForbidden();

        TandaTanganDokumen::factory()->create(['id_dokumen' => $diminta->id_dokumen, 'id_user_penandatangan' => $hr->id_user]);
        $this->getJson('/api/v1/dokumen')->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/dokumen?perlu_ttd=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.aksi.perlu_tanda_tangan_saya', true);

        loginTalentPlacement($placement);
        $this->getJson('/api/v1/dokumen')->assertJsonCount(1, 'data');
    });

    it('bisa difilter jenis & status, dan menolak filter tidak valid (422)', function () {
        Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::Quotation]);
        Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::Pks, 'status_dokumen' => StatusDokumen::Ditandatangani]);
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/dokumen?jenis=quotation')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dokumen?status=ditandatangani')->assertJsonCount(1, 'data')->assertJsonPath('data.0.jenis_dokumen', 'pks');
        $this->getJson('/api/v1/dokumen?jenis=ktp')->assertUnprocessable()->assertJsonValidationErrors(['jenis']);
    });

    it('mengunduh file dengan nama asli; 404 jika file hilang', function () {
        $dokumen = Dokumen::factory()->create(['file_path' => 'dokumen/abc/quotation-001.pdf']);
        Storage::disk('local')->put($dokumen->file_path, '%PDF-1.4 isi');
        loginSebagai(Role::AccountManager);

        $this->get("/api/v1/dokumen/{$dokumen->id_dokumen}/download")
            ->assertOk()
            ->assertDownload('quotation-001.pdf');

        Storage::disk('local')->delete($dokumen->file_path);
        $this->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/download")
            ->assertNotFound()
            ->assertExactJson(['message' => 'File dokumen tidak ditemukan.']);
    });
});

describe('Penandatangan & tanda tangan (R27, R28)', function () {
    it('pengunggah menentukan penandatangan, lalu semua menandatangani → ditandatangani', function () {
        $am = loginSebagai(Role::AccountManager);
        $placement = Placement::factory()->create();
        $dokumen = Dokumen::factory()->create(['id_user_uploader' => $am->id_user, 'id_klien' => $placement->id_klien]);
        $hr = HrClient::factory()->create(['id_klien' => $placement->id_klien])->user;
        $manager = buatUser(Role::Manager);

        $this->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/calon-penandatangan")
            ->assertOk()
            ->assertJsonFragment(['id_user' => $hr->id_user, 'keterangan' => $placement->klien->nama_perusahaan]);

        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$hr->id_user, $manager->id_user]])
            ->assertOk()
            ->assertJsonPath('message', 'Penandatangan berhasil ditentukan. Dokumen menunggu tanda tangan.')
            ->assertJsonPath('data.status_dokumen', 'menunggu_ttd')
            ->assertJsonCount(2, 'data.penandatangan')
            ->assertJsonPath('data.penandatangan.0.status_ttd', 'menunggu');

        $this->actingAs($hr)
            ->post("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditandatangani', 'file_tanda_tangan' => gambarTtd()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Tanda tangan tersimpan. Menunggu penandatangan lain.')
            ->assertJsonPath('data.status_dokumen', 'menunggu_ttd');

        $this->actingAs($manager)
            ->post("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditandatangani', 'file_tanda_tangan' => gambarTtd()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Dokumen telah ditandatangani semua pihak.')
            ->assertJsonPath('data.status_dokumen', 'ditandatangani');

        $ttd = TandaTanganDokumen::where('id_user_penandatangan', $hr->id_user)->sole();
        expect($ttd->tanggal_ttd)->not->toBeNull();
        Storage::disk('local')->assertExists($ttd->file_tanda_tangan);
    });

    it('satu penolakan membuat dokumen ditolak; keputusan kedua ditolak (422)', function () {
        $dokumen = Dokumen::factory()->create(['status_dokumen' => StatusDokumen::MenungguTtd]);
        $manager = loginSebagai(Role::Manager);
        TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen, 'id_user_penandatangan' => $manager->id_user]);
        TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen]);

        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditolak'])
            ->assertOk()
            ->assertJsonPath('message', 'Dokumen ditolak.')
            ->assertJsonPath('data.status_dokumen', 'ditolak');

        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditolak'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.keputusan.0', 'Dokumen ini tidak sedang menunggu tanda tangan.');
    });

    it('menolak data tanda tangan tidak valid (422)', function (array $body, array $error) {
        $dokumen = Dokumen::factory()->create(['status_dokumen' => StatusDokumen::MenungguTtd]);
        $user = loginSebagai(Role::Manager);
        TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen, 'id_user_penandatangan' => $user->id_user]);

        $this->post("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", $body, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($error);
        expect(TandaTanganDokumen::where('id_user_penandatangan', $user->id_user)->sole()->status_ttd)->toBe(StatusTtd::Menunggu);
    })->with([
        'tanpa gambar' => [['keputusan' => 'ditandatangani'], ['file_tanda_tangan' => 'Gambar tanda tangan wajib diunggah untuk menandatangani.']],
        'bukan gambar' => [['keputusan' => 'ditandatangani', 'file_tanda_tangan' => UploadedFile::fake()->create('ttd.pdf', 10)], ['file_tanda_tangan' => 'Gambar tanda tangan harus berformat PNG atau JPG.']],
        'keputusan salah' => [['keputusan' => 'menunggu'], ['keputusan']],
    ]);

    it('menolak penandatangan yang tidak berhak dan dokumen yang bukan draft (422)', function () {
        $am = loginSebagai(Role::AccountManager);
        $dokumen = Dokumen::factory()->create(['id_user_uploader' => $am->id_user, 'id_klien' => Klien::factory()]);
        $hrLain = buatUser(Role::HrClient);
        $nonaktif = User::factory()->role(Role::Manager)->create(['status_aktif' => false]);

        foreach ([$hrLain, $nonaktif] as $calon) {
            $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$calon->id_user]])
                ->assertUnprocessable()
                ->assertJsonPath('errors.id_user.0', 'Penandatangan harus pegawai internal aktif, HR Client dari klien dokumen, atau talent pemilik dokumen.');
        }

        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$am->id_user, $am->id_user]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_user.0' => 'Penandatangan tidak boleh dipilih dua kali.']);

        $dokumen->update(['status_dokumen' => StatusDokumen::MenungguTtd]);
        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$am->id_user]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_user.0', 'Penandatangan hanya dapat ditentukan untuk dokumen berstatus draft.');
    });

    it('menolak selain pengunggah mengatur penandatangan dan selain penandatangan menandatangani (403)', function () {
        $dokumen = Dokumen::factory()->create(['status_dokumen' => StatusDokumen::MenungguTtd]);
        TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen]);
        loginSebagai(Role::Manager);

        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [1]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya pengunggah dokumen yang dapat menentukan penandatangan.']);
        $this->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/calon-penandatangan")->assertForbidden();
        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditolak'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda bukan penandatangan dokumen ini.']);
    });
});
