<?php

use App\Enums\Role;
use App\Models\Pegawai;
use App\Models\User;

describe('GET /pegawai', function () {
    it('menampilkan daftar pegawai dengan pagination untuk Manager', function () {
        loginSebagai(Role::Manager);
        Pegawai::factory()->talentSupport()->count(2)->create();

        $this->getJson('/api/v1/pegawai?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id_pegawai', 'nama_pegawai', 'jabatan', 'no_telepon', 'akun' => ['id_user', 'email', 'role', 'status_aktif']]], 'links', 'meta']);
    });

    it('bisa difilter berdasarkan role dan dicari berdasarkan nama atau email', function () {
        loginSebagai(Role::Manager);
        Pegawai::factory()->talentSupport()->create(['nama_pegawai' => 'Rina Kartika']);
        Pegawai::factory()->accountManager()->create(['nama_pegawai' => 'Andi Pratama']);

        $this->getJson('/api/v1/pegawai?role=talent_support')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_pegawai', 'Rina Kartika');

        $this->getJson('/api/v1/pegawai?search=andi')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_pegawai', 'Andi Pratama');
    });

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/pegawai?role=talent&per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'per_page']);
    });

    it('menolak role selain Manager (403)', function (Role $role) {
        loginSebagai($role);

        // Exact JSON: memastikan tidak ada detail exception/trace yang ikut terkirim.
        $this->getJson('/api/v1/pegawai')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Manager));

    it('menolak request tanpa login (401)', function () {
        $this->getJson('/api/v1/pegawai')->assertUnauthorized();
    });
});

describe('POST /pegawai', function () {
    $dataValid = fn (array $ubah = []) => array_merge([
        'email' => 'dimas@intellisys.test',
        'password' => 'rahasia123',
        'role' => 'talent_support',
        'nama_pegawai' => 'Dimas Prasetyo',
        'jabatan' => 'Talent Support',
        'no_telepon' => '0812-3456-7890',
    ], $ubah);

    it('membuat akun internal beserta profil pegawai yang bisa langsung login', function () use ($dataValid) {
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/pegawai', $dataValid())
            ->assertCreated()
            ->assertJsonPath('message', 'Pegawai berhasil ditambahkan')
            ->assertJsonPath('data.nama_pegawai', 'Dimas Prasetyo')
            ->assertJsonPath('data.akun.role', 'talent_support');

        $user = User::where('email', 'dimas@intellisys.test')->firstOrFail();
        expect($user->role)->toBe(Role::TalentSupport)
            ->and($user->pegawai->jabatan)->toBe('Talent Support');

        $this->postJson('/api/v1/auth/login', ['email' => 'dimas@intellisys.test', 'password' => 'rahasia123'])->assertOk();
    });

    it('menolak role non-internal dan email duplikat (422)', function () use ($dataValid) {
        loginSebagai(Role::Manager);
        User::factory()->create(['email' => 'dimas@intellisys.test']);

        $this->postJson('/api/v1/pegawai', $dataValid(['role' => 'talent']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'email']);
    });

    it('menolak data kosong (422)', function () {
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/pegawai', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'role', 'nama_pegawai', 'jabatan']);
    });

    it('menolak role selain Manager (403) tanpa membuat akun', function (Role $role) use ($dataValid) {
        loginSebagai($role);

        $this->postJson('/api/v1/pegawai', $dataValid())->assertForbidden();

        expect(User::where('email', 'dimas@intellisys.test')->exists())->toBeFalse();
    })->with(roleSelain(Role::Manager));
});

describe('GET /pegawai/{id}', function () {
    it('menampilkan detail pegawai beserta akunnya', function () {
        loginSebagai(Role::Manager);
        $pegawai = Pegawai::factory()->accountManager()->create();

        $this->getJson("/api/v1/pegawai/{$pegawai->id_pegawai}")
            ->assertOk()
            ->assertJsonPath('data.id_pegawai', $pegawai->id_pegawai)
            ->assertJsonPath('data.akun.email', $pegawai->user->email);
    });

    it('mengembalikan 404 untuk pegawai yang tidak ada', function () {
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/pegawai/99999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });

    it('menolak role selain Manager (403)', function (Role $role) {
        $pegawai = Pegawai::factory()->create();
        loginSebagai($role);

        $this->getJson("/api/v1/pegawai/{$pegawai->id_pegawai}")->assertForbidden();
    })->with(roleSelain(Role::Manager));
});

describe('PUT /pegawai/{id}', function () {
    it('mengubah hanya kolom yang dikirim', function () {
        loginSebagai(Role::Manager);
        $pegawai = Pegawai::factory()->accountManager()->create(['nama_pegawai' => 'Andi', 'jabatan' => 'Account Manager']);

        $this->putJson("/api/v1/pegawai/{$pegawai->id_pegawai}", ['jabatan' => 'Senior Account Manager'])
            ->assertOk()
            ->assertJsonPath('message', 'Pegawai berhasil diperbarui')
            ->assertJsonPath('data.nama_pegawai', 'Andi')
            ->assertJsonPath('data.jabatan', 'Senior Account Manager');
    });

    it('bisa mengganti email, role, dan password akun pegawai', function () {
        loginSebagai(Role::Manager);
        $pegawai = Pegawai::factory()->accountManager()->create();

        $this->putJson("/api/v1/pegawai/{$pegawai->id_pegawai}", [
            'email' => $pegawai->user->email,
            'role' => 'manager',
            'password' => 'passwordbaru1',
        ])->assertOk()->assertJsonPath('data.akun.role', 'manager');

        $this->postJson('/api/v1/auth/login', ['email' => $pegawai->user->email, 'password' => 'passwordbaru1'])->assertOk();
    });

    it('menolak email milik user lain dan role non-internal (422)', function () {
        loginSebagai(Role::Manager);
        $pegawai = Pegawai::factory()->create();
        $lain = User::factory()->create();

        $this->putJson("/api/v1/pegawai/{$pegawai->id_pegawai}", ['email' => $lain->email, 'role' => 'hr_client'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'role']);
    });

    it('mengembalikan 404 untuk pegawai yang tidak ada', function () {
        loginSebagai(Role::Manager);

        $this->putJson('/api/v1/pegawai/99999', ['jabatan' => 'X'])->assertNotFound();
    });

    it('menolak role selain Manager (403) tanpa mengubah data', function (Role $role) {
        $pegawai = Pegawai::factory()->create(['jabatan' => 'Account Manager']);
        loginSebagai($role);

        $this->putJson("/api/v1/pegawai/{$pegawai->id_pegawai}", ['jabatan' => 'Direktur'])->assertForbidden();

        expect($pegawai->refresh()->jabatan)->toBe('Account Manager');
    })->with(roleSelain(Role::Manager));
});
