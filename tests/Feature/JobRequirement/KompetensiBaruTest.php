<?php

use App\Enums\Role;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;

// R33: pembuat job requirement (HR Client/AM) boleh menambah kompetensi baru langsung dari form job requirement.

function dataJobKompetensiBaru(array $ubah = []): array
{
    return [
        'posisi' => 'Frontend Developer',
        'jumlah_kebutuhan' => 1,
        'sla_deadline' => now()->addDays(30)->toDateTimeString(),
        ...$ubah,
    ];
}

describe('POST /job-requirements dengan kompetensi_baru', function () {
    it('membuat kompetensi baru (kategori kosong) dan memasangnya bersama kompetensi master', function (Role $role) {
        loginSebagai($role);
        $master = Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel']);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru([
            'id_klien' => Klien::factory()->create()->id_klien,
            'kompetensi' => [$master->id_kompetensi],
            'kompetensi_baru' => ['  Golang   Fiber  '],
        ]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.kompetensi')
            ->assertJsonFragment(['nama_kompetensi' => 'Golang Fiber', 'kategori' => null]);

        $baru = Kompetensi::where('nama_kompetensi', 'Golang Fiber')->sole();
        expect($baru->kategori)->toBeNull()
            ->and(JobRequirement::sole()->kompetensi->modelKeys())->toEqualCanonicalizing([$master->id_kompetensi, $baru->id_kompetensi]);
    })->with([Role::HrClient, Role::AccountManager]);

    it('memakai kompetensi lama jika namanya sama setelah normalisasi', function (string $diketik) {
        loginSebagai(Role::HrClient);
        $lama = Kompetensi::factory()->create(['nama_kompetensi' => 'ReactJS']);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => [$diketik]]))
            ->assertCreated()
            ->assertJsonCount(1, 'data.kompetensi')
            ->assertJsonPath('data.kompetensi.0.id_kompetensi', $lama->id_kompetensi)
            ->assertJsonPath('data.kompetensi.0.nama_kompetensi', 'ReactJS');

        expect(Kompetensi::count())->toBe(1);
    })->with(['reactjs', 'React.js', 'React JS', 'react-js', 'REACT_JS']);

    it('tidak menggandakan kompetensi yang sama dengan id yang juga dipilih', function () {
        loginSebagai(Role::HrClient);
        $lama = Kompetensi::factory()->create(['nama_kompetensi' => 'Node.js']);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru([
            'kompetensi' => [$lama->id_kompetensi],
            'kompetensi_baru' => ['nodejs'],
        ]))
            ->assertCreated()
            ->assertJsonCount(1, 'data.kompetensi');
    });

    it('membuat satu kompetensi saja untuk nama dobel di dalam kompetensi_baru', function () {
        loginSebagai(Role::AccountManager);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru([
            'id_klien' => Klien::factory()->create()->id_klien,
            'kompetensi_baru' => ['Laravel', 'laravel', 'Docker'],
        ]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.kompetensi');

        expect(Kompetensi::pluck('nama_kompetensi')->all())->toEqualCanonicalizing(['Laravel', 'Docker']);
    });

    it('lolos dengan hanya kompetensi_baru tanpa kompetensi', function () {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => ['Flutter']]))
            ->assertCreated()
            ->assertJsonPath('data.kompetensi.0.nama_kompetensi', 'Flutter');
    });

    it('menolak jika kompetensi dan kompetensi_baru sama-sama kosong (422)', function (array $data) {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru($data))
            ->assertUnprocessable()
            ->assertJsonPath('errors.kompetensi.0', 'Pilih minimal 1 kompetensi.');

        expect(JobRequirement::count())->toBe(0);
    })->with([
        'tidak dikirim' => [[]],
        'array kosong' => [['kompetensi' => [], 'kompetensi_baru' => []]],
    ]);

    it('menolak kompetensi_baru tidak valid (422) tanpa membuat kompetensi', function (array $kompetensiBaru, string $kolom) {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => $kompetensiBaru]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$kolom]);

        expect(Kompetensi::count())->toBe(0);
    })->with([
        'kurang dari 2 karakter setelah trim' => [[' a '], 'kompetensi_baru.0'],
        'lebih dari 100 karakter' => [[str_repeat('x', 101)], 'kompetensi_baru.0'],
        'tanpa huruf/angka' => [['.-_'], 'kompetensi_baru.0'],
        'lebih dari 10 item' => [array_map(fn ($i) => "Skill {$i}", range(1, 11)), 'kompetensi_baru'],
        'bukan teks' => [[123], 'kompetensi_baru.0'],
    ]);

    it('memakai pesan Bahasa Indonesia untuk kompetensi baru', function () {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => ['a']]))
            ->assertJsonPath('errors', ['kompetensi_baru.0' => ['Kolom kompetensi baru harus berisi minimal 2 karakter.']]);
    });

    it('membatalkan kompetensi baru jika job requirement gagal disimpan', function () {
        loginSebagai(Role::AccountManager);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru([
            'id_klien' => Klien::factory()->create()->id_klien,
            'kompetensi' => [999],
            'kompetensi_baru' => ['Kotlin'],
        ]))->assertUnprocessable();

        expect(Kompetensi::count())->toBe(0);
    });

    it('tetap menolak Talent, TS, dan Manager membuat job requirement (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => ['Rust']]))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(Kompetensi::count())->toBe(0);
    })->with(roleSelain(Role::HrClient, Role::AccountManager));
});

describe('PUT /job-requirements/{id} dengan kompetensi_baru', function () {
    it('menggantikan daftar lama dengan gabungan kompetensi dan kompetensi_baru', function () {
        $hr = loginSebagai(Role::HrClient);
        $job = JobRequirement::factory()->create(['id_klien' => $hr->idKlien(), 'status_req' => StatusReq::Draft]);
        $job->kompetensi()->attach(Kompetensi::factory()->count(2)->create());
        $tetap = Kompetensi::factory()->create(['nama_kompetensi' => 'MySQL']);

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", [
            'kompetensi' => [$tetap->id_kompetensi],
            'kompetensi_baru' => ['Tailwind CSS'],
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.kompetensi');

        expect($job->kompetensi()->pluck('nama_kompetensi')->all())->toEqualCanonicalizing(['MySQL', 'Tailwind CSS']);
    });

    it('boleh hanya kompetensi_baru dengan kompetensi berupa array kosong', function () {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan]);
        $job->kompetensi()->attach(Kompetensi::factory()->create());

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['kompetensi' => [], 'kompetensi_baru' => ['Vue.js']])
            ->assertOk()
            ->assertJsonCount(1, 'data.kompetensi')
            ->assertJsonPath('data.kompetensi.0.nama_kompetensi', 'Vue.js');
    });

    it('mengabaikan kompetensi_baru jika kompetensi tidak dikirim', function () {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft]);
        $job->kompetensi()->attach($lama = Kompetensi::factory()->create());

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => 'Baru', 'kompetensi_baru' => ['Svelte']])
            ->assertOk()
            ->assertJsonCount(1, 'data.kompetensi')
            ->assertJsonPath('data.kompetensi.0.id_kompetensi', $lama->id_kompetensi);

        expect(Kompetensi::where('nama_kompetensi', 'Svelte')->exists())->toBeFalse();
    });

    it('menolak kompetensi dan kompetensi_baru yang sama-sama kosong (422)', function () {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft]);

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['kompetensi' => [], 'kompetensi_baru' => []])
            ->assertUnprocessable()
            ->assertJsonPath('errors.kompetensi.0', 'Pilih minimal 1 kompetensi.');
    });
});

describe('Hak akses master kompetensi tidak berubah', function () {
    it('HR Client dan AM tetap ditolak menambah kompetensi lewat POST /kompetensi (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/kompetensi', ['nama_kompetensi' => 'Rust'])->assertForbidden();

        expect(Kompetensi::count())->toBe(0);
    })->with([Role::HrClient, Role::AccountManager]);

    it('kompetensi dari job requirement langsung bisa dipilih talent di profil', function () {
        loginSebagai(Role::HrClient);
        $this->postJson('/api/v1/job-requirements', dataJobKompetensiBaru(['kompetensi_baru' => ['Elixir']]))->assertCreated();
        $id = Kompetensi::where('nama_kompetensi', 'Elixir')->value('id_kompetensi');

        loginSebagai(Role::Talent);
        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [$id]])
            ->assertOk()
            ->assertJsonFragment(['nama_kompetensi' => 'Elixir']);
    });
});
