<?php

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Models\HrClient;
use App\Models\Klien;
use App\Models\Talent;
use App\Models\User;

describe('POST /auth/register/talent', function () {
    $dataValid = fn (array $ubah = []) => array_merge([
        'email' => 'budi@example.com',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'nama_lengkap' => 'Budi Santoso',
    ], $ubah);

    it('membuat user talent dengan profil belum lengkap dan langsung memberi token', function () use ($dataValid) {
        $response = $this->postJson('/api/v1/auth/register/talent', $dataValid())
            ->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil')
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonPath('data.user.role', 'talent')
            ->assertJsonPath('data.user.profil.nama_lengkap', 'Budi Santoso')
            ->assertJsonPath('data.user.profil.status_kelengkapan', 'belum_lengkap');

        $talent = Talent::firstOrFail();
        expect($talent->status_kelengkapan)->toBe(StatusKelengkapan::BelumLengkap)
            ->and($talent->user->role)->toBe(Role::Talent);

        $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')->assertOk();
    });

    it('mengabaikan role yang dikirim client', function () use ($dataValid) {
        $this->postJson('/api/v1/auth/register/talent', $dataValid(['role' => 'manager']))->assertCreated();

        expect(User::firstOrFail()->role)->toBe(Role::Talent);
    });

    it('menolak data tidak valid (422) dengan pesan Bahasa Indonesia', function () {
        $this->postJson('/api/v1/auth/register/talent', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'nama_lengkap'])
            ->assertJsonPath('errors.nama_lengkap.0', 'Kolom nama lengkap wajib diisi.');
    });

    it('menolak email yang sudah terdaftar dan konfirmasi password yang berbeda', function () use ($dataValid) {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/v1/auth/register/talent', $dataValid(['password_confirmation' => 'beda12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('POST /auth/register/hr-client', function () {
    $dataValid = fn (array $ubah = []) => array_merge([
        'email' => 'hr@majujaya.co.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'nama_hr' => 'Sari Wulandari',
        'jabatan' => 'HR Manager',
        'nama_perusahaan' => 'PT Maju Jaya',
        'alamat' => 'Jakarta',
    ], $ubah);

    it('membuat klien baru, user hr_client, dan profil HR', function () use ($dataValid) {
        $this->postJson('/api/v1/auth/register/hr-client', $dataValid())
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'hr_client')
            ->assertJsonPath('data.user.profil.nama_hr', 'Sari Wulandari')
            ->assertJsonPath('data.user.profil.klien.nama_perusahaan', 'PT Maju Jaya')
            ->assertJsonStructure(['data' => ['token']]);

        $hr = HrClient::firstOrFail();
        expect($hr->user->role)->toBe(Role::HrClient)
            ->and($hr->klien->nama_perusahaan)->toBe('PT Maju Jaya');
    });

    it('selalu membuat klien baru meskipun nama perusahaan sudah ada', function () use ($dataValid) {
        Klien::factory()->create(['nama_perusahaan' => 'PT Maju Jaya']);

        $this->postJson('/api/v1/auth/register/hr-client', $dataValid())->assertCreated();

        expect(Klien::where('nama_perusahaan', 'PT Maju Jaya')->count())->toBe(2);
    });

    it('menolak data tidak valid (422) tanpa membuat data apa pun', function () use ($dataValid) {
        $this->postJson('/api/v1/auth/register/hr-client', $dataValid(['nama_perusahaan' => '', 'nama_hr' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama_perusahaan', 'nama_hr']);

        expect(Klien::count())->toBe(0)
            ->and(User::count())->toBe(0);
    });
});
