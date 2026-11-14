<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\Bahasa;
use App\Models\HrClient;
use App\Models\Kompetensi;
use App\Models\PengalamanKerjaTalent;
use App\Models\RiwayatPendidikan;
use App\Models\ScreeningTalent;
use App\Models\SertifikatTalent;
use App\Models\Talent;
use App\Models\TalentPool;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * GET /screening/{id}: detail usulan kandidat untuk tinjauan AM sebelum diteruskan ke klien (feedback SUS 2026-11-14).
 */
beforeEach(fn () => Storage::fake('local'));

/**
 * Permintaan butuh 4 kompetensi & pengalaman 2 tahun; talent cocok 3 dan berpengalaman 1 tahun (Jan–Des 2023)
 * → skor dihitung ulang 0,8 × 75 + 0,2 × 50 = 70. Skor tersimpan sengaja berbeda (55,5).
 */
function usulanLengkap(): ScreeningTalent
{
    $job = rekrutmenJob(atribut: ['pengalaman_min' => 2]);
    $kompetensi = collect(['Laravel' => 'Backend', 'MySQL' => 'Database', 'REST API' => 'Backend', 'Docker' => 'DevOps'])
        ->map(fn (string $kategori, string $nama) => Kompetensi::factory()->create(['nama_kompetensi' => $nama, 'kategori' => $kategori]));
    $job->kompetensi()->attach($kompetensi->pluck('id_kompetensi'));

    $talent = Talent::factory()->create(['nama_lengkap' => 'Budi Santoso', 'file_cv' => 'cv/budi.pdf']);
    $talent->kompetensi()->attach($kompetensi->only(['Laravel', 'MySQL', 'REST API'])->pluck('id_kompetensi'));
    RiwayatPendidikan::factory()->for($talent)->create(['jenjang' => 'S1', 'nama_institusi' => 'Universitas Telkom', 'jurusan' => 'Informatika', 'tahun_mulai' => 2018, 'tahun_selesai' => 2022]);
    PengalamanKerjaTalent::factory()->for($talent)->create([
        'nama_perusahaan' => 'PT Maju', 'posisi' => 'Backend Developer', 'tanggal_mulai' => '2023-01-01', 'tanggal_selesai' => '2023-12-31',
        'masih_bekerja' => false, 'deskripsi' => 'Membangun REST API.',
    ]);
    $talent->bahasa()->attach(Bahasa::factory()->create(['nama_bahasa' => 'Inggris']), ['tingkat_kemampuan' => 'Menengah']);
    SertifikatTalent::factory()->for($talent)->create(['nama_sertifikat' => 'Laravel Certified', 'file_sertifikat' => "sertifikat/{$talent->id_talent}/1/laravel.pdf"]);

    $screening = rekrutmenScreeningLolos($job, $talent);
    $screening->update(['skor_kecocokan' => 55.5, 'catatan_screening' => 'Lulusan kampus mitra klien.']);

    return $screening;
}

describe('GET /screening/{id}', function () {
    it('menampilkan profil kandidat, catatan TS, dan rincian kecocokan untuk AM', function () {
        loginSebagai(Role::AccountManager);
        $s = usulanLengkap();
        $pool = TalentPool::where('id_talent', $s->id_talent)->sole();

        $this->getJson("/api/v1/screening/{$s->id_screening}")
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil')
            ->assertJsonPath('data.id_screening', $s->id_screening)
            ->assertJsonPath('data.skor_kecocokan', 55.5)
            ->assertJsonPath('data.catatan_screening', 'Lulusan kampus mitra klien.')
            ->assertJsonPath('data.status_usulan', 'menunggu_am')
            ->assertJsonPath('data.talent.nama_lengkap', 'Budi Santoso')
            ->assertJsonPath('data.talent.id_talent_pool', $pool->id_talent_pool)
            ->assertJsonPath('data.talent.ada_cv', true)
            ->assertJsonPath('data.talent.pendidikan', 'S1 Informatika – Universitas Telkom')
            ->assertJsonPath('data.talent.riwayat_pendidikan.0.nama_institusi', 'Universitas Telkom')
            ->assertJsonPath('data.talent.riwayat_pengalaman_kerja.0.posisi', 'Backend Developer')
            ->assertJsonPath('data.talent.riwayat_pengalaman_kerja.0.deskripsi', 'Membangun REST API.')
            ->assertJsonPath('data.talent.lama_pengalaman_tahun', 1)
            ->assertJsonPath('data.talent.kompetensi.0.nama_kompetensi', 'Laravel')
            ->assertJsonPath('data.talent.kompetensi.0.kategori', 'Backend')
            ->assertJsonPath('data.talent.bahasa.0.nama_bahasa', 'Inggris')
            ->assertJsonPath('data.talent.bahasa.0.tingkat_kemampuan', 'Menengah')
            ->assertJsonPath('data.talent.sertifikat.0.nama_sertifikat', 'Laravel Certified')
            ->assertJsonPath('data.talent.sertifikat.0.file_sertifikat', 'laravel.pdf')
            ->assertJsonPath('data.job_requirement.pengalaman_min', 2)
            ->assertJsonPath('data.job_requirement.jumlah_kompetensi', 4)
            ->assertJsonPath('data.rincian_kecocokan', [
                'skor_kecocokan' => 70,
                'skor_kompetensi' => 75,
                'skor_pengalaman' => 50,
                'bobot_kompetensi' => 80,
                'bobot_pengalaman' => 20,
                'kompetensi_cocok' => ['Laravel', 'MySQL', 'REST API'],
                'kompetensi_kurang' => ['Docker'],
                'lama_pengalaman' => 1,
                'pengalaman_min' => 2,
            ]);
    });

    it('terbuka untuk Talent Support dan Manager (hanya baca)', function (Role $role) {
        loginSebagai($role);
        $s = usulanLengkap();

        $this->getJson("/api/v1/screening/{$s->id_screening}")
            ->assertOk()
            ->assertJsonPath('data.rincian_kecocokan.skor_kecocokan', 70);
    })->with([Role::TalentSupport, Role::Manager]);

    it('tetap bisa dibuka setelah usulan tidak diteruskan, beserta alasannya', function () {
        loginSebagai(Role::AccountManager);
        $s = usulanLengkap();
        $s->update(['hasil_screening' => HasilScreening::TidakLolos, 'catatan_screening' => ScreeningTalent::gabungCatatan('Lulusan kampus mitra klien.', 'Klien minta S2.')]);

        $this->getJson("/api/v1/screening/{$s->id_screening}")
            ->assertOk()
            ->assertJsonPath('data.status_usulan', 'tidak_diteruskan')
            ->assertJsonPath('data.catatan_screening', 'Lulusan kampus mitra klien.')
            ->assertJsonPath('data.alasan_tidak_diteruskan', 'Klien minta S2.');
    });

    it('menolak role selain internal', function (Role $role) {
        loginSebagai($role);
        $s = usulanLengkap();

        $this->getJson("/api/v1/screening/{$s->id_screening}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::AccountManager, Role::TalentSupport, Role::Manager));

    it('menolak HR Client walaupun usulan itu untuk permintaan kliennya sendiri', function () {
        $s = usulanLengkap();
        $hr = HrClient::factory()->create(['id_klien' => $s->jobRequirement->id_klien]);
        Sanctum::actingAs($hr->user);

        $this->getJson("/api/v1/screening/{$s->id_screening}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    });

    it('404 jika usulan tidak ada', function () {
        loginSebagai(Role::AccountManager);

        $this->getJson('/api/v1/screening/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});

describe('Berkas kandidat yang baru diusulkan tidak terbuka untuk HR Client', function () {
    it('HR Client klien permintaan tidak bisa membuka sertifikat & CV sebelum kandidat diajukan ke kliennya', function () {
        $s = usulanLengkap();
        $sertifikat = $s->talent->sertifikat->first();
        Storage::disk('local')->put($sertifikat->file_sertifikat, '%PDF-1.4 sertifikat');
        $pool = TalentPool::where('id_talent', $s->id_talent)->sole();
        $hr = HrClient::factory()->create(['id_klien' => $s->jobRequirement->id_klien]);
        Sanctum::actingAs($hr->user);

        $this->getJson("/api/v1/sertifikat/{$sertifikat->id_sertifikat}/file")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke file sertifikat ini.']);
        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}/cv")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    });

    it('AM bisa membuka sertifikat kandidat yang masih berupa usulan', function () {
        loginSebagai(Role::AccountManager);
        $s = usulanLengkap();
        $sertifikat = $s->talent->sertifikat->first();
        Storage::disk('local')->put($sertifikat->file_sertifikat, '%PDF-1.4 sertifikat');

        $this->get("/api/v1/sertifikat/{$sertifikat->id_sertifikat}/file")->assertOk();
    });

    it('HR Client baru bisa membuka sertifikat setelah AM mengajukan kandidat ke kliennya', function () {
        $s = usulanLengkap();
        $sertifikat = $s->talent->sertifikat->first();
        Storage::disk('local')->put($sertifikat->file_sertifikat, '%PDF-1.4 sertifikat');
        \App\Models\PengajuanKandidat::factory()->create(['id_screening' => $s->id_screening, 'status_pengajuan' => StatusPengajuan::Diajukan]);
        $hr = HrClient::factory()->create(['id_klien' => $s->jobRequirement->id_klien]);
        Sanctum::actingAs($hr->user);

        $this->get("/api/v1/sertifikat/{$sertifikat->id_sertifikat}/file")->assertOk();
    });
});

describe('ScreeningTalentPolicy::view', function () {
    it('hanya mengizinkan internal; HR Client & talent ditolak walau middleware role dilewati', function (Role $role, bool $boleh) {
        $s = usulanLengkap();
        $user = $role === Role::HrClient
            ? HrClient::factory()->create(['id_klien' => $s->jobRequirement->id_klien])->user
            : buatUser($role);

        $hasil = Gate::forUser($user)->inspect('view', $s);

        expect($hasil->allowed())->toBe($boleh);
        if (! $boleh) {
            expect($hasil->message())->toBe('Anda tidak memiliki akses ke usulan kandidat ini.');
        }
    })->with([
        'account manager' => [Role::AccountManager, true],
        'talent support' => [Role::TalentSupport, true],
        'manager' => [Role::Manager, true],
        'hr client klien permintaan' => [Role::HrClient, false],
        'talent' => [Role::Talent, false],
    ]);
});
