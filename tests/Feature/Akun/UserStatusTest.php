<?php

use App\Enums\Role;
use App\Models\User;

describe('PATCH /users/{id}/status', function () {
    it('menonaktifkan akun, mencabut semua tokennya, dan menolak login berikutnya', function (Role $roleTarget) {
        loginSebagai(Role::Manager);
        $target = buatUser($roleTarget);
        $target->createToken('api');

        $this->patchJson("/api/v1/users/{$target->id_user}/status", ['status_aktif' => false])
            ->assertOk()
            ->assertJsonPath('message', 'Akun berhasil dinonaktifkan')
            ->assertJsonPath('data.status_aktif', false);

        expect($target->refresh()->status_aktif)->toBeFalse()
            ->and($target->tokens()->count())->toBe(0);

        $this->postJson('/api/v1/auth/login', ['email' => $target->email, 'password' => 'password'])->assertForbidden();
    })->with(fn () => collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => [$r]])->all());

    it('mengaktifkan kembali akun sehingga bisa login lagi', function () {
        loginSebagai(Role::Manager);
        $target = User::factory()->nonaktif()->create();

        $this->patchJson("/api/v1/users/{$target->id_user}/status", ['status_aktif' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Akun berhasil diaktifkan');

        $this->postJson('/api/v1/auth/login', ['email' => $target->email, 'password' => 'password'])->assertOk();
    });

    it('menolak nilai status tidak valid (422)', function (array $body) {
        loginSebagai(Role::Manager);
        $target = User::factory()->create();

        $this->patchJson("/api/v1/users/{$target->id_user}/status", $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_aktif']);
    })->with([
        'kosong' => [[]],
        'bukan boolean' => [['status_aktif' => 'mungkin']],
    ]);

    it('menolak Manager mengubah status akunnya sendiri (403)', function () {
        $manager = loginSebagai(Role::Manager);

        $this->patchJson("/api/v1/users/{$manager->id_user}/status", ['status_aktif' => false])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak dapat mengubah status akun Anda sendiri.']);

        expect($manager->refresh()->status_aktif)->toBeTrue();
    });

    it('menolak role selain Manager (403)', function (Role $role) {
        $target = User::factory()->create();
        loginSebagai($role);

        $this->patchJson("/api/v1/users/{$target->id_user}/status", ['status_aktif' => false])->assertForbidden();

        expect($target->refresh()->status_aktif)->toBeTrue();
    })->with(roleSelain(Role::Manager));

    it('mengembalikan 404 untuk user yang tidak ada', function () {
        loginSebagai(Role::Manager);

        $this->patchJson('/api/v1/users/99999/status', ['status_aktif' => false])->assertNotFound();
    });
});
