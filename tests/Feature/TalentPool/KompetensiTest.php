<?php

use App\Enums\Role;
use App\Models\Kompetensi;

describe('GET /kompetensi', function () {
    it('menampilkan master kompetensi urut nama untuk semua role', function (Role $role) {
        loginSebagai($role);
        Kompetensi::factory()->create(['nama_kompetensi' => 'React', 'kategori' => 'Frontend']);
        Kompetensi::factory()->create(['nama_kompetensi' => 'Laravel', 'kategori' => 'Backend']);

        $this->getJson('/api/v1/kompetensi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0', ['id_kompetensi' => Kompetensi::where('nama_kompetensi', 'Laravel')->value('id_kompetensi'), 'nama_kompetensi' => 'Laravel', 'kategori' => 'Backend'])
            ->assertJsonPath('data.1.nama_kompetensi', 'React')
            ->assertJsonStructure(['data', 'links', 'meta']);
    })->with(Role::cases());

    it('bisa dicari berdasarkan nama dan difilter kategori', function () {
        loginSebagai(Role::Talent);
        Kompetensi::factory()->create(['nama_kompetensi' => 'MySQL', 'kategori' => 'Database']);
        Kompetensi::factory()->create(['nama_kompetensi' => 'PostgreSQL', 'kategori' => 'Database']);
        Kompetensi::factory()->create(['nama_kompetensi' => 'SQL Server Reporting', 'kategori' => 'Data']);

        $this->getJson('/api/v1/kompetensi?search=sql')->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/kompetensi?search=sql&kategori=Database')->assertJsonCount(2, 'data');
    });

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::Talent);

        $this->getJson('/api/v1/kompetensi?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    it('menolak request tanpa login (401)', function () {
        $this->getJson('/api/v1/kompetensi')->assertUnauthorized();
    });
});
