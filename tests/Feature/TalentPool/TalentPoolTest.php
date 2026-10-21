<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKetersediaan;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\PengalamanKerjaTalent;
use App\Models\RiwayatPendidikan;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\TalentPool;

describe('GET /talent-pool', function () {
    it('menampilkan daftar talent pool dengan pagination untuk role internal', function (Role $role) {
        loginSebagai($role);
        TalentPool::factory()->count(3)->create();

        $this->getJson('/api/v1/talent-pool?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [[
                'id_talent_pool', 'tanggal_masuk', 'status_ketersediaan', 'tag_keahlian',
                'talent' => ['id_talent', 'nama_lengkap', 'email', 'domisili', 'pendidikan', 'status_kandidat', 'kompetensi' => [['id_kompetensi', 'nama_kompetensi', 'kategori']], 'ada_cv'],
            ]], 'links', 'meta']);
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('meringkas pendidikan dari riwayat terbaru, dengan fallback ke kolom teks', function () {
        loginSebagai(Role::TalentSupport);

        $denganRiwayat = Talent::factory()->create(['nama_lengkap' => 'A Riwayat', 'pendidikan' => 'Teks lama']);
        RiwayatPendidikan::factory()->for($denganRiwayat)->create(['jenjang' => 'D3', 'jurusan' => 'Manajemen Informatika', 'nama_institusi' => 'Politeknik Negeri Jakarta', 'tahun_mulai' => 2014, 'tahun_selesai' => 2017]);
        RiwayatPendidikan::factory()->for($denganRiwayat)->create(['jenjang' => 'S1', 'jurusan' => 'Informatika', 'nama_institusi' => 'Telkom University', 'tahun_mulai' => 2018, 'tahun_selesai' => 2022]);
        TalentPool::factory()->for($denganRiwayat)->create();

        $tanpaRiwayat = Talent::factory()->create(['nama_lengkap' => 'B Teks', 'pendidikan' => 'S1 Sistem Informasi']);
        TalentPool::factory()->for($tanpaRiwayat)->create(['tanggal_masuk' => now()->subDay()]);

        $this->getJson('/api/v1/talent-pool')
            ->assertOk()
            ->assertJsonPath('data.0.talent.pendidikan', 'S1 Informatika – Telkom University')
            ->assertJsonPath('data.1.talent.pendidikan', 'S1 Sistem Informasi');
    });

    it('menganggap pendidikan yang masih berjalan sebagai yang terbaru', function () {
        loginSebagai(Role::TalentSupport);
        $talent = Talent::factory()->create();
        RiwayatPendidikan::factory()->for($talent)->create(['jenjang' => 'S1', 'jurusan' => 'Informatika', 'nama_institusi' => 'ITB', 'tahun_mulai' => 2016, 'tahun_selesai' => 2020]);
        RiwayatPendidikan::factory()->for($talent)->create(['jenjang' => 'S2', 'jurusan' => null, 'nama_institusi' => 'UI', 'tahun_mulai' => 2023, 'tahun_selesai' => null]);
        TalentPool::factory()->for($talent)->create();

        $this->getJson('/api/v1/talent-pool')->assertJsonPath('data.0.talent.pendidikan', 'S2 – UI');
    });

    it('bisa difilter status ketersediaan dan kompetensi (harus punya semua)', function () {
        loginSebagai(Role::TalentSupport);
        [$laravel, $react] = Kompetensi::factory()->count(2)->create();

        $keduanya = Talent::factory()->create(['nama_lengkap' => 'Punya Keduanya']);
        $keduanya->kompetensi()->attach([$laravel->id_kompetensi, $react->id_kompetensi]);
        TalentPool::factory()->for($keduanya)->create();

        $satu = Talent::factory()->create(['nama_lengkap' => 'Punya Satu']);
        $satu->kompetensi()->attach($laravel);
        TalentPool::factory()->for($satu)->create(['status_ketersediaan' => StatusKetersediaan::TidakTersedia]);

        $this->getJson('/api/v1/talent-pool?status=tidak_tersedia')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.talent.nama_lengkap', 'Punya Satu');

        $this->getJson("/api/v1/talent-pool?kompetensi[]={$laravel->id_kompetensi}&kompetensi[]={$react->id_kompetensi}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.talent.nama_lengkap', 'Punya Keduanya');

        $this->getJson("/api/v1/talent-pool?kompetensi[]={$laravel->id_kompetensi}")
            ->assertJsonCount(2, 'data');
    });

    it('bisa dicari berdasarkan nama, email, kompetensi, dan tag keahlian', function (string $kata, string $harapan) {
        loginSebagai(Role::TalentSupport);

        $rina = Talent::factory()->create(['nama_lengkap' => 'Rina Kartika']);
        $rina->user->update(['email' => 'rina.k@contoh.test']);
        TalentPool::factory()->for($rina)->create();

        $dodi = Talent::factory()->create(['nama_lengkap' => 'Dodi Saputra']);
        $dodi->kompetensi()->attach(Kompetensi::factory()->create(['nama_kompetensi' => 'Kubernetes']));
        TalentPool::factory()->for($dodi)->create(['tag_keahlian' => 'DevOps senior']);

        $this->getJson('/api/v1/talent-pool?search='.urlencode($kata))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.talent.nama_lengkap', $harapan);
    })->with([
        'nama' => ['kartika', 'Rina Kartika'],
        'email' => ['rina.k@', 'Rina Kartika'],
        'kompetensi' => ['kubern', 'Dodi Saputra'],
        'tag keahlian' => ['devops', 'Dodi Saputra'],
    ]);

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::TalentSupport);

        $this->getJson('/api/v1/talent-pool?status=sibuk&kompetensi[]=99999&per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'kompetensi.0', 'per_page']);
    });

    it('menolak talent dan HR Client (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/talent-pool')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::AccountManager, Role::TalentSupport, Role::Manager));

    it('menolak request tanpa login (401)', function () {
        $this->getJson('/api/v1/talent-pool')->assertUnauthorized();
    });
});

describe('GET /talent-pool/{id}', function () {
    it('menampilkan detail dengan riwayat pengalaman, lama pengalaman, dan riwayat screening', function () {
        loginSebagai(Role::AccountManager);
        $this->travelTo('2026-10-21');

        $talent = Talent::factory()->lengkap()->create(['pengalaman_kerja' => 'Teks bebas lama']);
        // Jan–Jun 2023 dan Apr–Des 2023 tumpang tindih → 1 tahun; Jan 2024–Jan 2025 → 1 tahun.
        PengalamanKerjaTalent::factory()->for($talent)->create(['nama_perusahaan' => 'PT A', 'posisi' => 'QA', 'tanggal_mulai' => '2023-01-01', 'tanggal_selesai' => '2023-06-30']);
        PengalamanKerjaTalent::factory()->for($talent)->create(['nama_perusahaan' => 'PT B', 'posisi' => 'Backend', 'tanggal_mulai' => '2023-04-01', 'tanggal_selesai' => '2024-01-01']);
        PengalamanKerjaTalent::factory()->for($talent)->create(['nama_perusahaan' => 'PT C', 'posisi' => 'Lead', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2025-01-01']);
        $pool = TalentPool::factory()->for($talent)->create();

        $klien = Klien::factory()->create(['nama_perusahaan' => 'PT Maju Jaya']);
        $job = JobRequirement::factory()->for($klien)->create(['posisi' => 'Backend Developer']);
        ScreeningTalent::factory()->for($talent)->for($job)->create(['skor_kecocokan' => 75, 'hasil_screening' => HasilScreening::Lolos]);

        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}")
            ->assertOk()
            ->assertJsonPath('data.id_talent_pool', $pool->id_talent_pool)
            ->assertJsonPath('data.talent.lama_pengalaman_tahun', 2)
            ->assertJsonCount(3, 'data.talent.riwayat_pengalaman_kerja')
            ->assertJsonPath('data.talent.riwayat_pengalaman_kerja.0', [
                'nama_perusahaan' => 'PT C',
                'posisi' => 'Lead',
                'tanggal_mulai' => '2024-01-01',
                'tanggal_selesai' => '2025-01-01',
                'masih_bekerja' => false,
            ])
            ->assertJsonMissingPath('data.talent.pengalaman_kerja')
            ->assertJsonPath('data.talent.riwayat_pendidikan.0.nama_institusi', $talent->riwayatPendidikan->first()->nama_institusi)
            ->assertJsonPath('data.riwayat_screening.0.posisi', 'Backend Developer')
            ->assertJsonPath('data.riwayat_screening.0.nama_perusahaan', 'PT Maju Jaya')
            ->assertJsonPath('data.riwayat_screening.0.skor_kecocokan', '75.00')
            ->assertJsonPath('data.riwayat_screening.0.hasil_screening', 'lolos');
    });

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::TalentSupport);

        $this->getJson('/api/v1/talent-pool/99999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });

    it('menolak talent dan HR Client, termasuk talent pemilik data (403)', function (Role $role) {
        $pool = TalentPool::factory()->create();
        loginSebagai($role);

        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::AccountManager, Role::TalentSupport, Role::Manager));
});

describe('PATCH /talent-pool/{id}', function () {
    it('mengubah status ketersediaan dan tag keahlian oleh Talent Support', function () {
        loginSebagai(Role::TalentSupport);
        $pool = TalentPool::factory()->create(['tag_keahlian' => 'Lama']);

        $this->patchJson("/api/v1/talent-pool/{$pool->id_talent_pool}", [
            'status_ketersediaan' => 'tidak_tersedia',
            'tag_keahlian' => 'Laravel, MySQL',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Data talent pool berhasil diperbarui.')
            ->assertJsonPath('data.status_ketersediaan', 'tidak_tersedia')
            ->assertJsonPath('data.tag_keahlian', 'Laravel, MySQL');

        expect($pool->fresh()->status_ketersediaan)->toBe(StatusKetersediaan::TidakTersedia);
    });

    it('bisa mengubah sebagian kolom saja, termasuk mengosongkan tag', function () {
        loginSebagai(Role::TalentSupport);
        $pool = TalentPool::factory()->create(['tag_keahlian' => 'Lama']);

        $this->patchJson("/api/v1/talent-pool/{$pool->id_talent_pool}", ['tag_keahlian' => null])
            ->assertOk()
            ->assertJsonPath('data.tag_keahlian', null)
            ->assertJsonPath('data.status_ketersediaan', 'tersedia');
    });

    it('menolak data tidak valid (422)', function (array $body, string $kolom) {
        loginSebagai(Role::TalentSupport);
        $pool = TalentPool::factory()->create();

        $this->patchJson("/api/v1/talent-pool/{$pool->id_talent_pool}", $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$kolom]);
    })->with([
        'body kosong' => [[], 'status_ketersediaan'],
        'status tidak dikenal' => [['status_ketersediaan' => 'sibuk'], 'status_ketersediaan'],
        'status null' => [['status_ketersediaan' => null], 'status_ketersediaan'],
        'tag terlalu panjang' => [['tag_keahlian' => str_repeat('a', 256)], 'tag_keahlian'],
    ]);

    it('menolak role selain Talent Support (403)', function (Role $role) {
        $pool = TalentPool::factory()->create();
        loginSebagai($role);

        $this->patchJson("/api/v1/talent-pool/{$pool->id_talent_pool}", ['status_ketersediaan' => 'tidak_tersedia'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect($pool->fresh()->status_ketersediaan)->toBe(StatusKetersediaan::Tersedia);
    })->with(roleSelain(Role::TalentSupport));

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::TalentSupport);

        $this->patchJson('/api/v1/talent-pool/99999', ['status_ketersediaan' => 'tersedia'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});
