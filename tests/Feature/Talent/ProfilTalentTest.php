<?php

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Models\Kompetensi;
use App\Models\PengalamanKerjaTalent;
use App\Models\RiwayatPendidikan;
use App\Models\Talent;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Login sebagai talent baru (profil belum lengkap) dan kembalikan profilnya.
 */
function loginSebagaiTalent(): Talent
{
    return loginSebagai(Role::Talent)->talent;
}

$riwayatPendidikan = fn (array $ubah = []) => array_merge([
    'jenjang' => 'S1',
    'nama_institusi' => 'Universitas Indonesia',
    'jurusan' => 'Teknik Informatika',
    'tahun_mulai' => 2017,
    'tahun_selesai' => 2021,
], $ubah);

$riwayatPengalaman = fn (array $ubah = []) => array_merge([
    'nama_perusahaan' => 'PT Niaga Digital',
    'posisi' => 'Backend Developer',
    'jenis_pekerjaan' => 'Full-time',
    'lokasi' => 'Jakarta',
    'tanggal_mulai' => '2021-08-01',
    'tanggal_selesai' => '2023-07-31',
    'masih_bekerja' => false,
], $ubah);

describe('GET /talent/profil', function () {
    it('menampilkan profil talent yang sedang login beserta relasinya', function () {
        $talent = loginSebagaiTalent();
        RiwayatPendidikan::factory()->for($talent)->create(['nama_institusi' => 'Universitas Gunadarma']);
        PengalamanKerjaTalent::factory()->for($talent)->create(['posisi' => 'QA Engineer']);
        $talent->kompetensi()->attach(Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel']));

        $this->getJson('/api/v1/talent/profil')
            ->assertOk()
            ->assertJsonPath('message', 'Profil talent berhasil diambil.')
            ->assertJsonPath('data.id_talent', $talent->id_talent)
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap')
            ->assertJsonPath('data.ada_cv', false)
            ->assertJsonPath('data.riwayat_pendidikan.0.nama_institusi', 'Universitas Gunadarma')
            ->assertJsonPath('data.riwayat_pengalaman_kerja.0.posisi', 'QA Engineer')
            ->assertJsonPath('data.kompetensi.0.nama_kompetensi', 'Laravel')
            ->assertJsonMissingPath('data.file_cv')
            ->assertJsonStructure(['data' => ['sertifikat', 'bahasa', 'tentang_saya', 'domisili', 'gaji_harapan']]);
    });

    it('hanya menampilkan profil sendiri, bukan profil talent lain', function () {
        $lain = Talent::factory()->lengkap()->create(['nama_lengkap' => 'Talent Lain']);
        $talent = loginSebagaiTalent();

        $this->getJson('/api/v1/talent/profil')
            ->assertOk()
            ->assertJsonPath('data.id_talent', $talent->id_talent)
            ->assertJsonMissing(['nama_lengkap' => 'Talent Lain'])
            ->assertJsonMissing(['nama_institusi' => $lain->riwayatPendidikan->first()->nama_institusi]);
    });

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/talent/profil')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Talent));

    it('menolak akun talent yang belum punya profil (403)', function () {
        Sanctum::actingAs(User::factory()->role(Role::Talent)->create());

        $this->getJson('/api/v1/talent/profil')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Profil talent tidak ditemukan atau akses ditolak.']);
    });

    it('menolak request tanpa login (401)', function () {
        $this->getJson('/api/v1/talent/profil')->assertUnauthorized();
    });
});

describe('PUT /talent/profil', function () use ($riwayatPendidikan, $riwayatPengalaman) {
    it('memperbarui data profil dan riwayat', function () use ($riwayatPendidikan, $riwayatPengalaman) {
        $talent = loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil', [
            'nama_lengkap' => 'Budi Santoso',
            'tanggal_lahir' => '1999-05-12',
            'domisili' => 'Jakarta Selatan',
            'tentang_saya' => 'Backend developer.',
            'gaji_harapan' => 9000000,
            'linkedin' => 'https://www.linkedin.com/in/budi',
            'riwayat_pendidikan' => [$riwayatPendidikan()],
            'riwayat_pengalaman_kerja' => [
                $riwayatPengalaman(),
                $riwayatPengalaman(['nama_perusahaan' => 'PT Kini', 'tanggal_mulai' => '2023-08-01', 'tanggal_selesai' => '2024-01-01', 'masih_bekerja' => true]),
            ],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Profil talent berhasil diperbarui.')
            ->assertJsonPath('data.nama_lengkap', 'Budi Santoso')
            ->assertJsonPath('data.tanggal_lahir', '1999-05-12')
            ->assertJsonPath('data.domisili', 'Jakarta Selatan')
            ->assertJsonCount(1, 'data.riwayat_pendidikan')
            ->assertJsonCount(2, 'data.riwayat_pengalaman_kerja');

        $talent->refresh();
        $masihBekerja = $talent->pengalamanKerjaTalent->firstWhere('nama_perusahaan', 'PT Kini');

        expect($talent->gaji_harapan)->toBe('9000000.00')
            ->and($masihBekerja->masih_bekerja)->toBeTrue()
            ->and($masihBekerja->tanggal_selesai)->toBeNull();
    });

    it('mengganti seluruh riwayat lama jika dikirim, dan mempertahankannya jika tidak dikirim', function () use ($riwayatPendidikan) {
        $talent = loginSebagaiTalent();
        RiwayatPendidikan::factory()->for($talent)->count(2)->create();

        $this->putJson('/api/v1/talent/profil', ['domisili' => 'Bandung'])->assertOk();
        expect($talent->riwayatPendidikan()->count())->toBe(2);

        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$riwayatPendidikan(['nama_institusi' => 'ITB'])]])
            ->assertOk()
            ->assertJsonCount(1, 'data.riwayat_pendidikan')
            ->assertJsonPath('data.riwayat_pendidikan.0.nama_institusi', 'ITB');
    });

    it('menolak data tidak valid (422)', function () use ($riwayatPendidikan, $riwayatPengalaman) {
        loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil', [
            'nama_lengkap' => '',
            'tanggal_lahir' => now()->addDay()->toDateString(),
            'gaji_harapan' => -1,
            'linkedin' => 'bukan-url',
            'riwayat_pendidikan' => [
                $riwayatPendidikan(['jenjang' => null]),
                $riwayatPendidikan(['tahun_mulai' => 2021, 'tahun_selesai' => 2019]),
            ],
            'riwayat_pengalaman_kerja' => [
                $riwayatPengalaman(['tanggal_selesai' => null]),
                $riwayatPengalaman(['tanggal_mulai' => '2023-01-01', 'tanggal_selesai' => '2022-01-01']),
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama_lengkap', 'tanggal_lahir', 'gaji_harapan', 'linkedin',
                'riwayat_pendidikan.0.jenjang',
                'riwayat_pendidikan.1.tahun_selesai',
                'riwayat_pengalaman_kerja.0.tanggal_selesai',
                'riwayat_pengalaman_kerja.1.tanggal_selesai',
            ])
            ->assertJsonValidationErrors([
                'nama_lengkap' => 'Nama lengkap wajib diisi.',
                'riwayat_pengalaman_kerja.0.tanggal_selesai' => 'Tanggal selesai wajib diisi jika tidak sedang bekerja di sana.',
                'riwayat_pendidikan.1.tahun_selesai' => 'Tahun selesai tidak boleh sebelum tahun mulai.',
                'riwayat_pengalaman_kerja.1.tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]);
    });

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->putJson('/api/v1/talent/profil', ['nama_lengkap' => 'Penyusup'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Talent));

    it('tidak bisa mengubah profil talent lain maupun kolom yang diatur sistem', function () {
        $lain = Talent::factory()->create(['nama_lengkap' => 'Talent Lain']);
        $talent = loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil', [
            'id_talent' => $lain->id_talent,
            'id_user' => $lain->id_user,
            'nama_lengkap' => 'Nama Baru',
            'status_kelengkapan' => 'lengkap',
            'status_kandidat' => 'ditempatkan',
            'file_cv' => 'cv/palsu.pdf',
        ])
            ->assertOk()
            ->assertJsonPath('data.id_talent', $talent->id_talent);

        expect($lain->fresh()->nama_lengkap)->toBe('Talent Lain')
            ->and($talent->fresh())
            ->nama_lengkap->toBe('Nama Baru')
            ->id_user->toBe($talent->id_user)
            ->status_kelengkapan->toBe(StatusKelengkapan::BelumLengkap)
            ->status_kandidat->value->toBe('tersedia')
            ->file_cv->toBeNull();
    });
});

describe('PUT /talent/profil/kompetensi', function () {
    it('mengganti daftar kompetensi talent', function () {
        $talent = loginSebagaiTalent();
        $talent->kompetensi()->attach($lama = Kompetensi::factory()->create());
        $baru = Kompetensi::factory()->count(2)->create();

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => $baru->pluck('id_kompetensi')->all()])
            ->assertOk()
            ->assertJsonPath('message', 'Kompetensi talent berhasil diperbarui.')
            ->assertJsonCount(2, 'data.kompetensi');

        expect($talent->kompetensi()->pluck('kompetensi.id_kompetensi')->sort()->values()->all())
            ->toBe($baru->pluck('id_kompetensi')->sort()->values()->all())
            ->and($talent->kompetensi()->whereKey($lama->id_kompetensi)->exists())->toBeFalse();
    });

    it('menolak data tidak valid (422)', function (Closure $body, string $kolom) {
        loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil/kompetensi', $body())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$kolom]);
    })->with([
        'tidak dikirim' => [fn () => [], 'id_kompetensi'],
        'bukan array' => [fn () => ['id_kompetensi' => 'Laravel'], 'id_kompetensi'],
        'tidak ada di master' => [fn () => ['id_kompetensi' => [99999]], 'id_kompetensi.0'],
        'duplikat' => [function () {
            $id = Kompetensi::factory()->create()->id_kompetensi;

            return ['id_kompetensi' => [$id, $id]];
        }, 'id_kompetensi.0'],
    ]);

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [Kompetensi::factory()->create()->id_kompetensi]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Talent));

    it('tidak mengubah kompetensi talent lain', function () {
        $lain = Talent::factory()->lengkap()->create();
        $kompetensiLain = $lain->kompetensi()->pluck('kompetensi.id_kompetensi')->all();
        loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [Kompetensi::factory()->create()->id_kompetensi]])
            ->assertOk();

        expect($lain->kompetensi()->pluck('kompetensi.id_kompetensi')->all())->toBe($kompetensiLain);
    });
});

describe('R4 & R5: status kelengkapan dan talent pool', function () use ($riwayatPendidikan) {
    it('menjadi lengkap dan masuk talent pool setelah ada riwayat pendidikan dan kompetensi', function () use ($riwayatPendidikan) {
        $talent = loginSebagaiTalent();
        $kompetensi = Kompetensi::factory()->create();

        // Baru riwayat pendidikan: belum lengkap.
        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$riwayatPendidikan()]])
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap');
        expect($talent->talentPool()->exists())->toBeFalse();

        // Ditambah kompetensi: lengkap dan otomatis masuk pool.
        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [$kompetensi->id_kompetensi]])
            ->assertJsonPath('data.status_kelengkapan', 'lengkap');

        $pool = $talent->talentPool()->first();
        expect($pool)->not->toBeNull()
            ->and($pool->status_ketersediaan)->toBe(StatusKetersediaan::Tersedia)
            ->and($pool->tanggal_masuk->isToday())->toBeTrue();
    });

    it('menjadi lengkap jika kompetensi sudah ada lalu riwayat pendidikan ditambahkan', function () use ($riwayatPendidikan) {
        $talent = loginSebagaiTalent();

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [Kompetensi::factory()->create()->id_kompetensi]])
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap');

        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$riwayatPendidikan()]])
            ->assertJsonPath('data.status_kelengkapan', 'lengkap');

        expect($talent->talentPool()->count())->toBe(1);
    });

    it('tidak mewajibkan kolom pendidikan dan pengalaman_kerja (teks bebas)', function () use ($riwayatPendidikan) {
        $talent = loginSebagaiTalent();
        $talent->kompetensi()->attach(Kompetensi::factory()->create());

        $this->putJson('/api/v1/talent/profil', [
            'pendidikan' => null,
            'pengalaman_kerja' => null,
            'riwayat_pendidikan' => [$riwayatPendidikan()],
        ])->assertJsonPath('data.status_kelengkapan', 'lengkap');
    });

    it('kembali belum lengkap jika riwayat pendidikan atau kompetensi dikosongkan, tanpa menghapus entri pool', function () use ($riwayatPendidikan) {
        $talent = loginSebagaiTalent();
        $talent->kompetensi()->attach(Kompetensi::factory()->create());
        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$riwayatPendidikan()]])
            ->assertJsonPath('data.status_kelengkapan', 'lengkap');

        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => []])
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap');

        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$riwayatPendidikan()]])
            ->assertJsonPath('data.status_kelengkapan', 'lengkap');

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => []])
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap');

        // R5 hanya menambah; pool tidak dobel dan tidak dihapus.
        expect($talent->talentPool()->count())->toBe(1);
    });
});
