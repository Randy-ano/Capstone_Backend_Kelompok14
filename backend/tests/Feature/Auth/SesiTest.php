<?php

use App\Enums\Role;
use App\Models\User;

describe('GET /auth/me', function () {
    it('mengembalikan user beserta profil sesuai role', function (Role $role, string $kunciProfil) {
        $user = loginSebagai($role);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id_user', $user->id_user)
            ->assertJsonPath('data.role', $role->value)
            ->assertJsonPath("data.profil.{$kunciProfil}", $user->profil()->getKey());
    })->with([
        'talent' => [Role::Talent, 'id_talent'],
        'hr_client' => [Role::HrClient, 'id_hr_client'],
        'account_manager' => [Role::AccountManager, 'id_pegawai'],
        'talent_support' => [Role::TalentSupport, 'id_pegawai'],
        'manager' => [Role::Manager, 'id_pegawai'],
    ]);

    it('menyertakan data klien untuk HR Client', function () {
        $user = loginSebagai(Role::HrClient);

        $this->getJson('/api/v1/auth/me')
            ->assertJsonPath('data.profil.klien.id_klien', $user->idKlien());
    });

    it('tidak menampilkan password', function () {
        loginSebagai(Role::Talent);

        $this->getJson('/api/v1/auth/me')->assertJsonMissingPath('data.password');
    });

    it('menolak request tanpa token (401) dengan JSON Bahasa Indonesia', function () {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Anda belum login atau sesi telah berakhir.']);
    });

    it('tetap membalas JSON meskipun client tidak mengirim header Accept', function () {
        $this->get('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Anda belum login atau sesi telah berakhir.');
    });
});

describe('POST /auth/logout', function () {
    it('hanya menghapus token yang sedang dipakai', function () {
        $user = User::factory()->create();
        $tokenDipakai = $user->createToken('api')->plainTextToken;
        $tokenLain = $user->createToken('api')->plainTextToken;

        $this->withToken($tokenDipakai)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil');

        expect($user->tokens()->count())->toBe(1);

        $this->app['auth']->forgetGuards();
        $this->withToken($tokenDipakai)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($tokenLain)->getJson('/api/v1/auth/me')->assertOk();
    });

    it('menolak request tanpa token (401)', function () {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    });
});
