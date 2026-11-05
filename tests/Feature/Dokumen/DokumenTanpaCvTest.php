<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Models\Dokumen;
use App\Models\Placement;
use App\Models\TandaTanganDokumen;
use App\Models\Talent;

/**
 * Revisi 2026-11-05: menu Dokumen khusus dokumen kerja sama; CV tidak tampil di daftar, filter, maupun hitungan tanda tangan.
 */
describe('CV di luar menu Dokumen', function () {
    it('daftar dokumen tidak menampilkan CV untuk internal', function (Role $role) {
        loginSebagai($role);
        Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::Cv, 'id_talent' => Talent::factory()]);
        $quotation = Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::Quotation]);

        $this->getJson('/api/v1/dokumen')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_dokumen', $quotation->id_dokumen);
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('talent tidak melihat CV miliknya di daftar dokumen, tetapi tetap melihat kontrak kerjanya', function () {
        // Menu dokumen talent terbuka setelah ditempatkan (middleware talent.ditempatkan).
        $user = loginTalentPlacement(Placement::factory()->create());
        Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::Cv, 'id_talent' => $user->talent->id_talent]);
        $kontrak = Dokumen::factory()->create(['jenis_dokumen' => JenisDokumen::KontrakKerja, 'id_talent' => $user->talent->id_talent]);

        $this->getJson('/api/v1/dokumen')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_dokumen', $kontrak->id_dokumen);
    });

    it('menolak filter jenis cv (422)', function () {
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/dokumen?jenis=cv')->assertUnprocessable()->assertJsonValidationErrors(['jenis']);
    });

    it('kartu "Perlu tanda tangan Anda" tidak menghitung CV', function () {
        $manager = loginSebagai(Role::Manager);
        foreach ([JenisDokumen::Cv, JenisDokumen::Pks] as $jenis) {
            $dokumen = Dokumen::factory()->create(['jenis_dokumen' => $jenis, 'status_dokumen' => StatusDokumen::MenungguTtd]);
            TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen, 'id_user_penandatangan' => $manager->id_user]);
        }

        $kartu = collect($this->getJson('/api/v1/dashboard/ringkasan')->json('data.kartu'))->pluck('nilai', 'kunci');
        expect($kartu['ttd_menunggu'])->toBe(1);

        $this->getJson('/api/v1/dokumen?perlu_ttd=1')->assertJsonCount(1, 'data');
    });
});
