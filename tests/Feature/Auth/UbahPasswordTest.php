<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('PUT /auth/password', function () {
    it('semua role bisa mengubah password sendiri', function (Role $role) {
        $user = loginSebagai($role);

        $this->putJson('/api/v1/auth/password', [
            'password_lama' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => 'Password berhasil diubah.']);

        expect(Hash::check('rahasiabaru123', $user->fresh()->password))->toBeTrue();
    })->with([Role::Talent, Role::HrClient, Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('login memakai password baru; sesi perangkat lain keluar, sesi saat ini tetap', function () {
        $user = User::factory()->create(['email' => 'budi@contoh.com', 'role' => Role::Talent]);
        $sesiIni = $user->createToken('auth')->plainTextToken;
        $sesiLain = $user->createToken('auth')->plainTextToken;

        $this->withToken($sesiIni)->putJson('/api/v1/auth/password', [
            'password_lama' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])->assertOk();

        expect($user->tokens()->count())->toBe(1);
        app('auth')->forgetGuards();
        $this->withToken($sesiIni)->getJson('/api/v1/auth/me')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($sesiLain)->getJson('/api/v1/auth/me')->assertUnauthorized();

        app('auth')->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['email' => 'budi@contoh.com', 'password' => 'password'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => 'budi@contoh.com', 'password' => 'rahasiabaru123'])->assertOk();
    });

    it('menolak password lama salah, konfirmasi beda, terlalu pendek, atau sama dengan lama (422)', function () {
        $user = loginSebagai(Role::Talent);

        $this->putJson('/api/v1/auth/password', ['password_lama' => 'salah123', 'password' => 'rahasiabaru123', 'password_confirmation' => 'rahasiabaru123'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.password_lama.0', 'Password lama tidak sesuai.');

        $this->putJson('/api/v1/auth/password', ['password_lama' => 'password', 'password' => 'pendek', 'password_confirmation' => 'beda'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->putJson('/api/v1/auth/password', ['password_lama' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Password baru harus berbeda dari password lama.');

        $this->putJson('/api/v1/auth/password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password_lama', 'password']);

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });

    it('menolak tanpa login (401)', function () {
        $this->putJson('/api/v1/auth/password', ['password_lama' => 'password'])->assertUnauthorized();
    });
});
