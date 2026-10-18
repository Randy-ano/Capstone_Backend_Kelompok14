<?php

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

describe('POST /auth/login', function () {
    it('mengembalikan token, user, role, dan profil', function (Role $role) {
        $user = buatUser($role);

        $response = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonPath('data.user.id_user', $user->id_user)
            ->assertJsonPath('data.user.role', $role->value)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id_user', 'email', 'role', 'status_aktif', 'profil']]]);

        expect($response->json('data.user.profil'))->not->toBeNull()
            ->and(PersonalAccessToken::findToken($response->json('data.token'))->tokenable->is($user))->toBeTrue();
    })->with(fn () => collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => [$r]])->all());

    it('sesuai contoh kontrak JSON untuk Talent Support', function () {
        $user = buatUser(Role::TalentSupport);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.profil.id_pegawai', $user->pegawai->id_pegawai)
            ->assertJsonPath('data.user.profil.nama_pegawai', $user->pegawai->nama_pegawai)
            ->assertJsonPath('data.user.profil.jabatan', 'Talent Support');
    });

    it('menolak password salah atau email tidak terdaftar dengan pesan yang sama (422)', function (string $email, string $password) {
        User::factory()->create(['email' => 'ada@intellisys.test']);

        $this->postJson('/api/v1/auth/login', compact('email', 'password'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email atau password salah.');
    })->with([
        'password salah' => ['ada@intellisys.test', 'salah123'],
        'email tidak terdaftar' => ['tidakada@intellisys.test', 'password'],
    ]);

    it('menolak input kosong (422)', function () {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('menolak akun nonaktif (403) tanpa membuat token', function () {
        $user = User::factory()->nonaktif()->create();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Akun Anda dinonaktifkan. Silakan hubungi Manager PT Intellisys.']);

        expect($user->tokens()->count())->toBe(0);
    });

    it('tidak membocorkan status nonaktif jika password salah', function () {
        $user = User::factory()->nonaktif()->create();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'salah123'])
            ->assertUnprocessable();
    });
});
