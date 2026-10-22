<?php

use App\Enums\Role;
use App\Models\Bahasa;
use App\Models\SertifikatTalent;

$sertifikat = fn (array $ubah = []) => array_merge([
    'nama_sertifikat' => 'AWS Certified Developer',
    'penerbit' => 'Amazon Web Services',
    'tanggal_terbit' => '2024-03-01',
    'tanggal_kedaluwarsa' => '2027-03-01',
    'url_sertifikat' => 'https://www.credly.com/badges/contoh',
], $ubah);

describe('PUT /talent/profil: sertifikat & bahasa', function () use ($sertifikat) {
    it('menyimpan sertifikat dan bahasa, lalu menampilkannya di profil', function () use ($sertifikat) {
        loginSebagai(Role::Talent);

        $this->putJson('/api/v1/talent/profil', [
            'sertifikat' => [$sertifikat(), $sertifikat(['nama_sertifikat' => 'Scrum Master', 'tanggal_kedaluwarsa' => null, 'url_sertifikat' => null])],
            'bahasa' => [
                ['nama_bahasa' => 'Indonesia', 'tingkat_kemampuan' => 'Penutur Asli'],
                ['nama_bahasa' => 'Inggris', 'tingkat_kemampuan' => 'Menengah'],
            ],
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.sertifikat')
            ->assertJsonPath('data.sertifikat.0.nama_sertifikat', 'AWS Certified Developer')
            ->assertJsonPath('data.sertifikat.0.tanggal_kedaluwarsa', '2027-03-01')
            ->assertJsonCount(2, 'data.bahasa');

        $this->getJson('/api/v1/talent/profil')
            ->assertOk()
            ->assertJsonFragment(['nama_bahasa' => 'Inggris', 'tingkat_kemampuan' => 'Menengah']);
    });

    it('mengganti seluruh daftar lama jika dikirim, dan mempertahankannya jika tidak dikirim', function () use ($sertifikat) {
        $talent = loginSebagai(Role::Talent)->talent;
        SertifikatTalent::factory()->for($talent)->count(2)->create();

        $this->putJson('/api/v1/talent/profil', ['domisili' => 'Bandung'])->assertOk();
        expect($talent->sertifikat()->count())->toBe(2);

        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [$sertifikat()], 'bahasa' => [['nama_bahasa' => 'Jepang', 'tingkat_kemampuan' => 'Dasar']]])
            ->assertOk()
            ->assertJsonCount(1, 'data.sertifikat');

        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [], 'bahasa' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.sertifikat')
            ->assertJsonCount(0, 'data.bahasa');
    });

    it('mencocokkan nama bahasa ke master tanpa membedakan huruf besar/kecil', function () {
        $inggris = Bahasa::create(['nama_bahasa' => 'Inggris']);
        $talent = loginSebagai(Role::Talent)->talent;

        $this->putJson('/api/v1/talent/profil', ['bahasa' => [['nama_bahasa' => '  inggris ', 'tingkat_kemampuan' => 'Fasih']]])
            ->assertOk()
            ->assertJsonPath('data.bahasa.0.id_bahasa', $inggris->id_bahasa)
            ->assertJsonPath('data.bahasa.0.nama_bahasa', 'Inggris');

        expect(Bahasa::count())->toBe(1)
            ->and($talent->bahasa()->first()->pivot->tingkat_kemampuan)->toBe('Fasih');
    });

    it('menolak data sertifikat & bahasa tidak valid (422)', function () use ($sertifikat) {
        loginSebagai(Role::Talent);

        $this->putJson('/api/v1/talent/profil', [
            'sertifikat' => [
                $sertifikat(['nama_sertifikat' => '']),
                $sertifikat(['tanggal_terbit' => '2025-01-01', 'tanggal_kedaluwarsa' => '2024-01-01', 'url_sertifikat' => 'bukan-url']),
            ],
            'bahasa' => [
                ['nama_bahasa' => 'Inggris', 'tingkat_kemampuan' => 'Jago'],
                ['nama_bahasa' => 'Jerman', 'tingkat_kemampuan' => 'Dasar'],
                ['nama_bahasa' => 'jerman', 'tingkat_kemampuan' => 'Fasih'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sertifikat.0.nama_sertifikat',
                'sertifikat.1.url_sertifikat',
                'bahasa.0.tingkat_kemampuan',
                'bahasa.2.nama_bahasa',
            ]);

        $this->putJson('/api/v1/talent/profil', ['sertifikat' => [$sertifikat(['tanggal_terbit' => '2025-01-01', 'tanggal_kedaluwarsa' => '2024-01-01'])]])
            ->assertUnprocessable()
            ->assertJsonPath('errors', ['sertifikat.0.tanggal_kedaluwarsa' => ['Tanggal kedaluwarsa tidak boleh sebelum tanggal terbit.']]);
    });
});

it('menampilkan sertifikat & bahasa di CV', function () use ($sertifikat) {
    $talent = loginSebagai(Role::Talent)->talent;

    $this->putJson('/api/v1/talent/profil', [
        'sertifikat' => [$sertifikat()],
        'bahasa' => [['nama_bahasa' => 'Inggris', 'tingkat_kemampuan' => 'Menengah']],
    ])->assertOk();

    $html = view('pdf.talent-cv', ['talent' => $talent->fresh()->load(['user', ...App\Services\TalentService::RELASI_PROFIL])])->render();

    expect($html)->toContain('Certifications')
        ->toContain('AWS Certified Developer')
        ->toContain('Languages')
        ->toContain('Inggris')
        ->toContain('Menengah');
});
