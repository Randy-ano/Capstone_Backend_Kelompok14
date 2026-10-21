<?php

use App\Enums\Role;
use App\Models\JobRequirement;
use App\Models\Kompetensi;
use App\Models\Talent;

describe('GET /kompetensi (pemakaian & kategori)', function () {
    it('menyertakan daftar kategori di meta dan jumlah pemakaian jika diminta', function () {
        loginSebagai(Role::TalentSupport);
        $laravel = Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel', 'kategori' => 'Backend']);
        Kompetensi::factory()->create(['nama_kompetensi' => 'Figma', 'kategori' => 'Design']);
        Kompetensi::factory()->create(['nama_kompetensi' => 'Scrum', 'kategori' => null]);
        Talent::factory()->count(2)->create()->each(fn ($t) => $t->kompetensi()->attach($laravel));
        JobRequirement::factory()->create()->kompetensi()->attach($laravel);

        $this->getJson('/api/v1/kompetensi')
            ->assertOk()
            ->assertJsonPath('meta.kategori', ['Backend', 'Design'])
            ->assertJsonMissingPath('data.0.jumlah_talent');

        $this->getJson('/api/v1/kompetensi?dengan_pemakaian=1&search=laravel')
            ->assertOk()
            ->assertJsonPath('data.0.jumlah_talent', 2)
            ->assertJsonPath('data.0.jumlah_job_requirement', 1);
    });
});

describe('POST /kompetensi', function () {
    it('menambah kompetensi dengan nama & kategori yang dirapikan', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/kompetensi', ['nama_kompetensi' => '  Golang ', 'kategori' => ' Backend '])
            ->assertCreated()
            ->assertJsonPath('message', 'Kompetensi berhasil ditambahkan.')
            ->assertJsonPath('data.nama_kompetensi', 'Golang')
            ->assertJsonPath('data.kategori', 'Backend')
            ->assertJsonPath('data.jumlah_talent', 0)
            ->assertJsonPath('data.jumlah_job_requirement', 0);

        expect(Kompetensi::sole()->nama_kompetensi)->toBe('Golang');
    })->with([Role::TalentSupport, Role::Manager]);

    it('kategori kosong disimpan null', function () {
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/kompetensi', ['nama_kompetensi' => 'Scrum', 'kategori' => '   '])
            ->assertCreated()
            ->assertJsonPath('data.kategori', null);
    });

    it('menolak data tidak valid (422)', function (array $body, string $kolom, ?string $pesan) {
        loginSebagai(Role::TalentSupport);
        Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel']);

        $respon = $this->postJson('/api/v1/kompetensi', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$kolom]);

        if ($pesan) {
            $respon->assertJsonPath("errors.{$kolom}.0", $pesan);
        }
    })->with([
        'nama kosong' => [['nama_kompetensi' => '   '], 'nama_kompetensi', 'Kolom nama kompetensi wajib diisi.'],
        'nama ganda beda huruf' => [['nama_kompetensi' => 'laravel'], 'nama_kompetensi', 'Kompetensi laravel sudah ada.'],
        'nama terlalu panjang' => [['nama_kompetensi' => str_repeat('a', 101)], 'nama_kompetensi', null],
        'kategori terlalu panjang' => [['nama_kompetensi' => 'Go', 'kategori' => str_repeat('a', 101)], 'kategori', null],
    ]);

    it('menolak selain Talent Support dan Manager (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/kompetensi', ['nama_kompetensi' => 'Golang'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(Kompetensi::count())->toBe(0);
    })->with(roleSelain(Role::TalentSupport, Role::Manager));
});

describe('PUT /kompetensi/{id}', function () {
    it('mengubah nama dan kategori; perubahan terlihat di talent yang memakainya', function () {
        loginSebagai(Role::TalentSupport);
        $k = Kompetensi::factory()->create(['nama_kompetensi' => 'Golang', 'kategori' => 'Backend']);
        $talent = Talent::factory()->create();
        $talent->kompetensi()->attach($k);

        $this->putJson("/api/v1/kompetensi/{$k->id_kompetensi}", ['nama_kompetensi' => 'Go', 'kategori' => null])
            ->assertOk()
            ->assertJsonPath('message', 'Kompetensi berhasil diperbarui.')
            ->assertJsonPath('data.nama_kompetensi', 'Go')
            ->assertJsonPath('data.kategori', null)
            ->assertJsonPath('data.jumlah_talent', 1);

        expect($talent->kompetensi()->sole()->nama_kompetensi)->toBe('Go');
    });

    it('boleh menyimpan nama yang sama dengan huruf berbeda untuk dirinya sendiri', function () {
        loginSebagai(Role::Manager);
        $k = Kompetensi::factory()->create(['nama_kompetensi' => 'mysql']);

        $this->putJson("/api/v1/kompetensi/{$k->id_kompetensi}", ['nama_kompetensi' => 'MySQL'])
            ->assertOk()
            ->assertJsonPath('data.nama_kompetensi', 'MySQL');
    });

    it('menolak nama milik kompetensi lain dan body kosong (422)', function () {
        loginSebagai(Role::TalentSupport);
        Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel']);
        $k = Kompetensi::factory()->create(['nama_kompetensi' => 'Lumen']);

        $this->putJson("/api/v1/kompetensi/{$k->id_kompetensi}", ['nama_kompetensi' => 'LARAVEL'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nama_kompetensi.0', 'Kompetensi LARAVEL sudah ada.');

        $this->putJson("/api/v1/kompetensi/{$k->id_kompetensi}", [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nama_kompetensi.0', 'Kirim minimal satu kolom: nama kompetensi atau kategori.');
    });

    it('menolak selain Talent Support dan Manager (403)', function (Role $role) {
        loginSebagai($role);
        $k = Kompetensi::factory()->create(['nama_kompetensi' => 'Golang']);

        $this->putJson("/api/v1/kompetensi/{$k->id_kompetensi}", ['nama_kompetensi' => 'Go'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect($k->fresh()->nama_kompetensi)->toBe('Golang');
    })->with(roleSelain(Role::TalentSupport, Role::Manager));

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::Manager);

        $this->putJson('/api/v1/kompetensi/999', ['nama_kompetensi' => 'Go'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});

describe('DELETE /kompetensi/{id}', function () {
    it('menghapus kompetensi yang belum dipakai', function (Role $role) {
        loginSebagai($role);
        $k = Kompetensi::factory()->create();

        $this->deleteJson("/api/v1/kompetensi/{$k->id_kompetensi}")
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => 'Kompetensi berhasil dihapus.']);

        expect(Kompetensi::count())->toBe(0);
    })->with([Role::TalentSupport, Role::Manager]);

    it('menolak menghapus kompetensi yang masih dipakai talent atau job requirement (422)', function () {
        loginSebagai(Role::TalentSupport);
        $k = Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel']);
        Talent::factory()->create()->kompetensi()->attach($k);
        JobRequirement::factory()->count(2)->create()->each(fn ($j) => $j->kompetensi()->attach($k));

        $this->deleteJson("/api/v1/kompetensi/{$k->id_kompetensi}")
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_kompetensi.0', 'Kompetensi Laravel masih dipakai oleh 1 talent dan 2 job requirement, sehingga tidak dapat dihapus.');

        expect(Kompetensi::count())->toBe(1);
    });

    it('menolak selain Talent Support dan Manager (403)', function (Role $role) {
        loginSebagai($role);
        $k = Kompetensi::factory()->create();

        $this->deleteJson("/api/v1/kompetensi/{$k->id_kompetensi}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(Kompetensi::count())->toBe(1);
    })->with(roleSelain(Role::TalentSupport, Role::Manager));

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::TalentSupport);

        $this->deleteJson('/api/v1/kompetensi/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});
