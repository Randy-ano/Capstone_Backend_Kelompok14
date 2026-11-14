<?php

use App\Enums\Role;
use App\Models\Kompetensi;
use App\Models\Talent;

/**
 * Checklist kelengkapan profil (R4) di GET/PUT /talent/profil (feedback SUS 2026-11-14).
 */
function syaratProfil(array $terpenuhi): array
{
    return [
        ['kunci' => 'nama_lengkap', 'label' => 'Nama lengkap', 'terpenuhi' => $terpenuhi[0]],
        ['kunci' => 'riwayat_pendidikan', 'label' => 'Riwayat pendidikan (min. 1)', 'terpenuhi' => $terpenuhi[1]],
        ['kunci' => 'kompetensi', 'label' => 'Kompetensi (min. 1)', 'terpenuhi' => $terpenuhi[2]],
    ];
}

describe('kelengkapan_profil', function () {
    it('talent baru: hanya nama terpenuhi (1/3), belum masuk talent pool', function () {
        loginSebagai(Role::Talent);

        $this->getJson('/api/v1/talent/profil')
            ->assertOk()
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap')
            ->assertJsonPath('data.kelengkapan_profil', [
                'syarat' => syaratProfil([true, false, false]),
                'terpenuhi' => 1,
                'total' => 3,
                'lengkap' => false,
                'di_talent_pool' => false,
            ]);
    });

    it('bertambah setiap syarat dipenuhi dan menjadi lengkap + masuk talent pool (respons PUT ikut diperbarui)', function () {
        loginSebagai(Role::Talent);
        $pendidikan = ['jenjang' => 'S1', 'nama_institusi' => 'Universitas Indonesia', 'jurusan' => 'Informatika', 'tahun_mulai' => 2018, 'tahun_selesai' => 2022];

        $this->putJson('/api/v1/talent/profil', ['riwayat_pendidikan' => [$pendidikan]])
            ->assertOk()
            ->assertJsonPath('data.kelengkapan_profil.syarat', syaratProfil([true, true, false]))
            ->assertJsonPath('data.kelengkapan_profil.terpenuhi', 2)
            ->assertJsonPath('data.kelengkapan_profil.lengkap', false);

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [Kompetensi::factory()->create()->id_kompetensi]])
            ->assertOk()
            ->assertJsonPath('data.status_kelengkapan', 'lengkap')
            ->assertJsonPath('data.kelengkapan_profil.terpenuhi', 3)
            ->assertJsonPath('data.kelengkapan_profil.lengkap', true)
            ->assertJsonPath('data.kelengkapan_profil.di_talent_pool', true);

        $this->getJson('/api/v1/talent/profil')->assertJsonPath('data.kelengkapan_profil.lengkap', true);
    });

    it('kembali belum lengkap bila kompetensi dikosongkan; entri talent pool tetap ada', function () {
        $talent = loginSebagai(Role::Talent)->talent;
        $talent->riwayatPendidikan()->create(['jenjang' => 'S1', 'nama_institusi' => 'ITB']);
        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => [Kompetensi::factory()->create()->id_kompetensi]])
            ->assertJsonPath('data.kelengkapan_profil.lengkap', true);

        $this->putJson('/api/v1/talent/profil/kompetensi', ['id_kompetensi' => []])
            ->assertOk()
            ->assertJsonPath('data.status_kelengkapan', 'belum_lengkap')
            ->assertJsonPath('data.kelengkapan_profil.syarat', syaratProfil([true, true, false]))
            ->assertJsonPath('data.kelengkapan_profil.lengkap', false)
            ->assertJsonPath('data.kelengkapan_profil.di_talent_pool', true);
    });

    it('nama kosong/spasi tidak memenuhi syarat nama (sumber yang sama dengan status_kelengkapan)', function () {
        $talent = Talent::factory()->create(['nama_lengkap' => '   ']);

        expect(collect($talent->syaratKelengkapan())->firstWhere('kunci', 'nama_lengkap')['terpenuhi'])->toBeFalse();
    });

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/talent/profil')->assertForbidden();
    })->with(roleSelain(Role::Talent));
});
