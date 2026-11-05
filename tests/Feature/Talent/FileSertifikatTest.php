<?php

use App\Enums\Role;
use App\Models\HrClient;
use App\Models\Klien;
use App\Models\SertifikatTalent;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Storage::fake('local'));

function talentDenganSertifikat(): array
{
    $user = loginSebagai(Role::Talent);
    $sertifikat = SertifikatTalent::factory()->for($user->talent)->create(['nama_sertifikat' => 'Laravel Certified Developer']);

    return [$user, $sertifikat];
}

function unggahSertifikat(SertifikatTalent $sertifikat, UploadedFile $file)
{
    return test()->post("/api/v1/talent/sertifikat/{$sertifikat->id_sertifikat}/file", ['file' => $file], ['Accept' => 'application/json']);
}

describe('POST /talent/sertifikat/{id}/file', function () {
    it('menyimpan file di disk private dan mengembalikan nama file (tanpa path)', function (string $nama, string $mime, string $jenis) {
        [, $sertifikat] = talentDenganSertifikat();

        unggahSertifikat($sertifikat, UploadedFile::fake()->create($nama, 300, $mime))
            ->assertOk()
            ->assertJsonPath('message', 'File sertifikat berhasil diunggah.')
            ->assertJsonPath('data.file_sertifikat', $nama)
            ->assertJsonPath('data.jenis_file', $jenis);

        $path = $sertifikat->fresh()->file_sertifikat;
        expect($path)->toBe("sertifikat/{$sertifikat->id_talent}/{$sertifikat->id_sertifikat}/{$nama}");
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    })->with([
        'pdf' => ['sertifikat-laravel.pdf', 'application/pdf', 'pdf'],
        'jpg' => ['sertifikat.jpg', 'image/jpeg', 'gambar'],
        'png' => ['sertifikat.png', 'image/png', 'gambar'],
    ]);

    it('ganti file menghapus file lama dari storage', function () {
        [, $sertifikat] = talentDenganSertifikat();
        unggahSertifikat($sertifikat, UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf'))->assertOk();
        $lama = $sertifikat->fresh()->file_sertifikat;

        unggahSertifikat($sertifikat, UploadedFile::fake()->create('baru.png', 100, 'image/png'))
            ->assertOk()
            ->assertJsonPath('data.file_sertifikat', 'baru.png');

        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($sertifikat->fresh()->file_sertifikat);
    });

    it('menolak file tidak valid (422)', function (?UploadedFile $file, string $pesan) {
        [, $sertifikat] = talentDenganSertifikat();

        $this->post("/api/v1/talent/sertifikat/{$sertifikat->id_sertifikat}/file", $file ? ['file' => $file] : [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', $pesan);
        expect($sertifikat->fresh()->file_sertifikat)->toBeNull();
    })->with([
        'kosong' => [null, 'Pilih file sertifikat yang akan diunggah.'],
        'tipe salah' => [fn () => UploadedFile::fake()->create('sertifikat.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'), 'File sertifikat harus berformat PDF, JPG, atau PNG.'],
        'lebih dari 2 MB' => [fn () => UploadedFile::fake()->create('besar.pdf', 2049, 'application/pdf'), 'Ukuran file sertifikat maksimal 2 MB.'],
    ]);

    it('talent lain dan role lain tidak dapat mengunggah (403)', function (Role $role) {
        $sertifikat = SertifikatTalent::factory()->create();
        loginSebagai($role);

        unggahSertifikat($sertifikat, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->assertForbidden();
    })->with([Role::Talent, Role::HrClient, Role::AccountManager, Role::TalentSupport, Role::Manager]);
});

describe('GET /sertifikat/{id}/file', function () {
    beforeEach(function () {
        $this->sertifikat = SertifikatTalent::factory()->create();
        $this->path = "sertifikat/{$this->sertifikat->id_talent}/{$this->sertifikat->id_sertifikat}/sertifikat-aws.pdf";
        Storage::disk('local')->put($this->path, '%PDF-1.4 isi sertifikat');
        $this->sertifikat->update(['file_sertifikat' => $this->path]);
    });

    it('pemilik dan internal bisa membuka file secara inline', function (?Role $role) {
        $role === null ? Sanctum::actingAs($this->sertifikat->talent->user) : loginSebagai($role);

        $response = $this->get("/api/v1/sertifikat/{$this->sertifikat->id_sertifikat}/file")->assertOk();
        expect($response->headers->get('content-disposition'))->toStartWith('inline')->toContain('sertifikat-aws.pdf')
            ->and($response->streamedContent())->toBe('%PDF-1.4 isi sertifikat');
    })->with([
        'talent pemilik' => [null],
        'Talent Support' => [Role::TalentSupport],
        'Manager' => [Role::Manager],
        'Account Manager' => [Role::AccountManager],
    ]);

    it('HR Client hanya jika talent pernah diajukan ke kliennya', function () {
        $klien = Klien::factory()->create();
        Sanctum::actingAs(HrClient::factory()->create(['id_klien' => $klien->id_klien])->user);
        $url = "/api/v1/sertifikat/{$this->sertifikat->id_sertifikat}/file";

        $this->getJson($url)->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke file sertifikat ini.']);

        // Diajukan ke klien lain: tetap ditolak.
        $pengajuan = rekrutmenPengajuan(rekrutmenJob(Klien::factory()->create()->id_klien), talent: $this->sertifikat->talent);
        $this->getJson($url)->assertForbidden();

        // Permintaan kebutuhan milik klien HR ini: boleh.
        $pengajuan->jobRequirement->update(['id_klien' => $klien->id_klien]);
        $this->get($url)->assertOk();
    });

    it('talent lain ditolak (403); file belum diunggah → 404', function () {
        loginSebagai(Role::Talent);
        $this->getJson("/api/v1/sertifikat/{$this->sertifikat->id_sertifikat}/file")->assertForbidden();

        $tanpaFile = SertifikatTalent::factory()->create();
        loginSebagai(Role::Manager);
        $this->getJson("/api/v1/sertifikat/{$tanpaFile->id_sertifikat}/file")
            ->assertNotFound()
            ->assertExactJson(['message' => 'File sertifikat belum diunggah.']);
    });

    it('tanpa login ditolak (401)', function () {
        $this->getJson("/api/v1/sertifikat/{$this->sertifikat->id_sertifikat}/file")->assertUnauthorized();
    });
});

describe('PUT /talent/profil: file sertifikat tetap ada', function () {
    it('memperbarui baris berdasarkan id tanpa menghilangkan file; baris yang dihapus ikut menghapus filenya', function () {
        [$user, $sertifikat] = talentDenganSertifikat();
        $lainnya = SertifikatTalent::factory()->for($user->talent)->create(['nama_sertifikat' => 'Scrum Master']);
        unggahSertifikat($sertifikat, UploadedFile::fake()->create('laravel.pdf', 100, 'application/pdf'))->assertOk();
        unggahSertifikat($lainnya, UploadedFile::fake()->create('scrum.png', 100, 'image/png'))->assertOk();
        [$pathLaravel, $pathScrum] = [$sertifikat->fresh()->file_sertifikat, $lainnya->fresh()->file_sertifikat];

        // Simpan profil: Laravel diubah namanya, Scrum Master tidak dikirim (dihapus), satu sertifikat baru.
        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [
            ['id_sertifikat' => $sertifikat->id_sertifikat, 'nama_sertifikat' => 'Laravel Certified Developer 11', 'penerbit' => 'Laravel'],
            ['nama_sertifikat' => 'Docker Certified Associate'],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data.sertifikat')
            ->assertJsonFragment(['id_sertifikat' => $sertifikat->id_sertifikat, 'nama_sertifikat' => 'Laravel Certified Developer 11', 'file_sertifikat' => 'laravel.pdf']);

        expect($sertifikat->fresh()->file_sertifikat)->toBe($pathLaravel)
            ->and(SertifikatTalent::find($lainnya->id_sertifikat))->toBeNull();
        Storage::disk('local')->assertExists($pathLaravel);
        Storage::disk('local')->assertMissing($pathScrum);

        // Simpan ulang dengan data yang sama: file tetap ada.
        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [['id_sertifikat' => $sertifikat->id_sertifikat, 'nama_sertifikat' => 'Laravel Certified Developer 11']]])->assertOk();
        expect($sertifikat->fresh()->file_sertifikat)->toBe($pathLaravel);
        Storage::disk('local')->assertExists($pathLaravel);
    });

    it('menolak id_sertifikat milik talent lain atau ganda (422)', function () {
        [, $sertifikat] = talentDenganSertifikat();
        $milikLain = SertifikatTalent::factory()->for(Talent::factory())->create();

        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [['id_sertifikat' => $milikLain->id_sertifikat, 'nama_sertifikat' => 'Curian']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sertifikat.0.id_sertifikat']);
        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [
            ['id_sertifikat' => $sertifikat->id_sertifikat, 'nama_sertifikat' => 'A'],
            ['id_sertifikat' => $sertifikat->id_sertifikat, 'nama_sertifikat' => 'B'],
        ]])->assertUnprocessable();
        expect($milikLain->fresh()->nama_sertifikat)->not->toBe('Curian');
    });
});

describe('Sertifikat di detail talent', function () {
    it('detail pengajuan dan talent pool menampilkan sertifikat beserta nama file', function () {
        $talent = Talent::factory()->create();
        $sertifikat = SertifikatTalent::factory()->for($talent)->create(['file_sertifikat' => 'sertifikat/1/1/aws.pdf']);
        $job = rekrutmenJob();
        $pengajuan = rekrutmenPengajuan($job, talent: $talent);

        loginSebagai(Role::TalentSupport);
        $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")
            ->assertOk()
            ->assertJsonPath('data.talent.sertifikat.0.id_sertifikat', $sertifikat->id_sertifikat)
            ->assertJsonPath('data.talent.sertifikat.0.file_sertifikat', 'aws.pdf')
            ->assertJsonPath('data.talent.sertifikat.0.jenis_file', 'pdf');
    });
});
