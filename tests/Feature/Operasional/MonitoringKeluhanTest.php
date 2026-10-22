<?php

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusPlacement;
use App\Models\LaporanKeluhan;
use App\Models\MonitoringTalent;
use App\Models\Placement;
use App\Models\Talent;

describe('GET & POST /monitoring (R23)', function () {
    it('Manager mencatat monitoring untuk placement mana pun, termasuk yang sudah selesai', function () {
        $placement = Placement::factory()->create(['status_placement' => StatusPlacement::Selesai]);
        $manager = loginSebagai(Role::Manager);

        $this->postJson('/api/v1/monitoring', [
            'id_placement' => $placement->id_placement,
            'hasil_monitoring' => 'Kinerja baik.',
            'catatan_tindak_lanjut' => 'Evaluasi ulang bulan depan.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Monitoring berhasil dicatat.')
            ->assertJsonPath('data.manager.id_pegawai', $manager->pegawai->id_pegawai)
            ->assertJsonPath('data.placement.id_placement', $placement->id_placement)
            ->assertJsonStructure(['data' => ['id_monitoring', 'tanggal_monitoring', 'hasil_monitoring', 'catatan_tindak_lanjut', 'manager', 'placement']]);
    });

    it('menolak data tidak valid (422)', function () {
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/monitoring', ['id_placement' => 999, 'tanggal_monitoring' => now()->addDay()->toDateTimeString()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_placement', 'hasil_monitoring', 'tanggal_monitoring' => 'Tanggal monitoring tidak boleh di masa depan.']);
    });

    it('AM dan Manager melihat daftar terbaru dulu, bisa difilter placement dan dicari nama talent', function (Role $role) {
        $budi = Placement::factory()->for(Talent::factory()->lengkap()->create(['nama_lengkap' => 'Budi Monitor']))->create();
        MonitoringTalent::factory()->for($budi)->create(['tanggal_monitoring' => now()->subDays(2)]);
        MonitoringTalent::factory()->for($budi)->create(['tanggal_monitoring' => now()]);
        MonitoringTalent::factory()->create();
        loginSebagai($role);

        $this->getJson('/api/v1/monitoring')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson("/api/v1/monitoring?id_placement={$budi->id_placement}")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tanggal_monitoring', now()->toIso8601String());
        $this->getJson('/api/v1/monitoring?search=budi monitor')->assertJsonCount(2, 'data');
    })->with([Role::AccountManager, Role::Manager]);

    it('menolak role lain melihat (403) dan selain Manager mencatat (403)', function () {
        foreach ([Role::Talent, Role::HrClient, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->getJson('/api/v1/monitoring')->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
        }

        loginSebagai(Role::AccountManager);
        $this->postJson('/api/v1/monitoring', ['id_placement' => Placement::factory()->create()->id_placement, 'hasil_monitoring' => 'X'])->assertForbidden();
        expect(MonitoringTalent::count())->toBe(0);
    });
});

describe('GET & POST /laporan-keluhan (R24)', function () {
    it('HR Client membuat laporan untuk talent di kliennya dengan status baru', function () {
        $placement = Placement::factory()->create();
        $hr = loginHrKlien($placement);

        $this->postJson('/api/v1/laporan-keluhan', [
            'id_placement' => $placement->id_placement,
            'judul_laporan' => 'Sering terlambat',
            'deskripsi_keluhan' => 'Terlambat 4 kali dalam dua minggu.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Laporan keluhan berhasil dikirim.')
            ->assertJsonPath('data.status_laporan', 'baru')
            ->assertJsonPath('data.pelapor.id_hr_client', $hr->hrClient->id_hr_client)
            ->assertJsonPath('data.tindak_lanjut', ['oleh' => null, 'hasil' => null, 'tanggal' => null]);
    });

    it('menolak placement di klien lain (403) dan data tidak valid (422)', function () {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/laporan-keluhan', ['id_placement' => Placement::factory()->create()->id_placement, 'judul_laporan' => 'X', 'deskripsi_keluhan' => 'Y'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda hanya dapat melaporkan talent yang ditempatkan di perusahaan Anda.']);
        $this->postJson('/api/v1/laporan-keluhan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_placement', 'judul_laporan', 'deskripsi_keluhan']);
        expect(LaporanKeluhan::count())->toBe(0);
    });

    it('menolak role selain HR Client membuat laporan (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/laporan-keluhan', ['id_placement' => Placement::factory()->create()->id_placement, 'judul_laporan' => 'X', 'deskripsi_keluhan' => 'Y'])
            ->assertForbidden();
    })->with(roleSelain(Role::HrClient));

    it('HR Client melihat laporan kliennya, AM/Manager semua, dengan ringkasan yang tidak terpengaruh filter', function () {
        $placement = Placement::factory()->create();
        LaporanKeluhan::factory()->for($placement)->create(['judul_laporan' => 'Komunikasi kurang']);
        LaporanKeluhan::factory()->create(['status_laporan' => StatusLaporan::Selesai]);
        LaporanKeluhan::factory()->create(['status_laporan' => StatusLaporan::Diproses]);

        loginHrKlien($placement);
        $this->getJson('/api/v1/laporan-keluhan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.ringkasan', ['total' => 1, 'baru' => 1, 'diproses' => 0, 'selesai' => 0]);

        loginSebagai(Role::AccountManager);
        $this->getJson('/api/v1/laporan-keluhan?status=selesai')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.ringkasan', ['total' => 3, 'baru' => 1, 'diproses' => 1, 'selesai' => 1]);
        $this->getJson('/api/v1/laporan-keluhan?search=komunikasi')->assertJsonCount(1, 'data');
    });

    it('menolak talent dan TS melihat laporan (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/laporan-keluhan')->assertForbidden();
    })->with([Role::Talent, Role::TalentSupport]);
});

describe('PATCH /laporan-keluhan/{id}/tindak-lanjut (R24)', function () {
    it('AM memproses lalu Manager menyelesaikan; laporan selesai tidak dapat diubah', function () {
        $laporan = LaporanKeluhan::factory()->create();

        $am = loginSebagai(Role::AccountManager);
        $this->patchJson("/api/v1/laporan-keluhan/{$laporan->id_laporan}/tindak-lanjut", ['status_laporan' => 'diproses'])
            ->assertOk()
            ->assertJsonPath('message', 'Laporan sedang diproses.')
            ->assertJsonPath('data.status_laporan', 'diproses')
            ->assertJsonPath('data.tindak_lanjut.oleh', $am->pegawai->nama_pegawai);

        $manager = loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/laporan-keluhan/{$laporan->id_laporan}/tindak-lanjut", ['status_laporan' => 'selesai', 'hasil_tindak_lanjut' => 'Talent sudah ditegur.'])
            ->assertOk()
            ->assertJsonPath('message', 'Laporan ditandai selesai.')
            ->assertJsonPath('data.tindak_lanjut.oleh', $manager->pegawai->nama_pegawai)
            ->assertJsonPath('data.tindak_lanjut.hasil', 'Talent sudah ditegur.');

        expect($laporan->fresh()->tanggal_tindak_lanjut)->not->toBeNull();

        $this->patchJson("/api/v1/laporan-keluhan/{$laporan->id_laporan}/tindak-lanjut", ['status_laporan' => 'diproses'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_laporan.0', 'Laporan yang sudah selesai tidak dapat diubah.');
    });

    it('menolak data tidak valid (422)', function (array $body, array $kolom) {
        $laporan = LaporanKeluhan::factory()->create();
        loginSebagai(Role::Manager);

        $this->patchJson("/api/v1/laporan-keluhan/{$laporan->id_laporan}/tindak-lanjut", $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($kolom);
    })->with([
        'status kosong' => [[], ['status_laporan']],
        'kembali ke baru' => [['status_laporan' => 'baru'], ['status_laporan']],
        'selesai tanpa hasil' => [['status_laporan' => 'selesai'], ['hasil_tindak_lanjut' => 'Hasil tindak lanjut wajib diisi saat laporan diselesaikan.']],
    ]);

    it('menolak HR Client, talent, TS (403) dan 404', function () {
        $laporan = LaporanKeluhan::factory()->create();

        foreach ([Role::HrClient, Role::Talent, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->patchJson("/api/v1/laporan-keluhan/{$laporan->id_laporan}/tindak-lanjut", ['status_laporan' => 'diproses'])->assertForbidden();
        }

        loginSebagai(Role::Manager);
        $this->patchJson('/api/v1/laporan-keluhan/999/tindak-lanjut', ['status_laporan' => 'diproses'])->assertNotFound();
        expect($laporan->fresh()->status_laporan)->toBe(StatusLaporan::Baru);
    });
});
