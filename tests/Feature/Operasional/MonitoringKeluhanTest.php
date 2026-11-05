<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusLaporan;
use App\Enums\StatusPlacement;
use App\Models\Dokumen;
use App\Models\LaporanKeluhan;
use App\Models\MonitoringTalent;
use App\Models\Notifikasi;
use App\Models\Placement;
use App\Models\Talent;

/** Dokumen Surat Peringatan milik talent penempatan. */
function dokumenSp(Placement $placement, array $ubah = []): Dokumen
{
    return Dokumen::factory()->create([
        'jenis_dokumen' => JenisDokumen::SuratPeringatan,
        'id_talent' => $placement->id_talent,
        'id_placement' => $placement->id_placement,
        'status_dokumen' => StatusDokumen::Ditandatangani,
        ...$ubah,
    ]);
}

describe('POST /monitoring (R23, revisi monitoring per talent)', function () {
    it('HR Client menambah catatan untuk talent di perusahaannya; pencatat & peran tercatat', function () {
        $placement = Placement::factory()->create();
        $hr = loginHrKlien($placement);

        $this->postJson('/api/v1/monitoring', [
            'id_placement' => $placement->id_placement,
            'hasil_monitoring' => 'Absensi lengkap; target harian tercapai.',
            'catatan_tindak_lanjut' => 'Pertahankan.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Catatan monitoring berhasil disimpan.')
            ->assertJsonPath('data.tipe', 'catatan')
            ->assertJsonPath('data.label_tipe', 'Catatan')
            ->assertJsonPath('data.nomor_sp', null)
            ->assertJsonPath('data.pencatat.id_user', $hr->id_user)
            ->assertJsonPath('data.pencatat.label_role', 'HR Client')
            ->assertJsonPath('data.dokumen', null)
            ->assertJsonPath('data.placement.id_placement', $placement->id_placement);

        expect(MonitoringTalent::sole()->id_manager)->toBeNull();
    });

    it('HR Client ditolak untuk talent klien lain dan tidak bisa memberi SP (403)', function () {
        $milik = Placement::factory()->create();
        loginHrKlien($milik);
        $lain = Placement::factory()->create();

        $this->postJson('/api/v1/monitoring', ['id_placement' => $lain->id_placement, 'hasil_monitoring' => 'X'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda hanya dapat mencatat monitoring untuk talent yang ditempatkan di perusahaan Anda.');
        $this->postJson('/api/v1/monitoring', ['id_placement' => $milik->id_placement, 'tipe' => 'sp', 'id_dokumen' => dokumenSp($milik)->id_dokumen, 'hasil_monitoring' => 'SP'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Surat Peringatan hanya dapat diberikan oleh Manager.');
        expect(MonitoringTalent::count())->toBe(0);
    });

    it('Manager memberi SP dengan dokumen Surat Peringatan; nomor SP berurutan; talent & HR Client dinotifikasi', function () {
        $placement = Placement::factory()->create();
        $hr = App\Models\HrClient::factory()->create(['id_klien' => $placement->id_klien])->user;
        $manager = loginSebagai(Role::Manager);

        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'hasil_monitoring' => 'Kunjungan rutin.'])->assertCreated();
        $sp1 = dokumenSp($placement);
        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'tipe' => 'sp', 'id_dokumen' => $sp1->id_dokumen, 'hasil_monitoring' => 'Terlambat berulang.'])
            ->assertCreated()
            ->assertJsonPath('message', 'Surat Peringatan berhasil dicatat.')
            ->assertJsonPath('data.tipe', 'sp')
            ->assertJsonPath('data.nomor_sp', 1)
            ->assertJsonPath('data.label_tipe', 'SP 1')
            ->assertJsonPath('data.pencatat.id_user', $manager->id_user)
            ->assertJsonPath('data.dokumen.id_dokumen', $sp1->id_dokumen);
        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'tipe' => 'sp', 'id_dokumen' => dokumenSp($placement)->id_dokumen, 'hasil_monitoring' => 'Masih terlambat.'])
            ->assertJsonPath('data.label_tipe', 'SP 2');

        expect(Notifikasi::where('id_user_penerima', $placement->talent->id_user)->where('tipe_notifikasi', 'sp_diberikan')->count())->toBe(2)
            ->and(Notifikasi::where('id_user_penerima', $hr->id_user)->where('tipe_notifikasi', 'sp_diberikan')->first()->pesan)->toContain('Surat Peringatan 1');
    });

    it('SP wajib dokumen Surat Peringatan milik talent yang belum dipakai dan tidak ditolak (422)', function (string $kasus, string $pesan) {
        $placement = Placement::factory()->create();
        loginSebagai(Role::Manager);
        $id = match ($kasus) {
            'tanpa dokumen' => null,
            'jenis lain' => dokumenSp($placement, ['jenis_dokumen' => JenisDokumen::Lainnya])->id_dokumen,
            'talent lain' => dokumenSp(Placement::factory()->create())->id_dokumen,
            'ditolak' => dokumenSp($placement, ['status_dokumen' => StatusDokumen::Ditolak])->id_dokumen,
            'sudah dipakai' => tap(dokumenSp($placement), fn ($d) => MonitoringTalent::factory()->for($placement)->create(['tipe' => 'sp', 'id_dokumen' => $d->id_dokumen]))->id_dokumen,
        };

        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'tipe' => 'sp', 'id_dokumen' => $id, 'hasil_monitoring' => 'SP'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_dokumen.0', $pesan);
    })->with([
        ['tanpa dokumen', 'Surat Peringatan wajib melampirkan dokumen Surat Peringatan.'],
        ['jenis lain', 'Pilih dokumen berjenis Surat Peringatan milik talent ini.'],
        ['talent lain', 'Pilih dokumen berjenis Surat Peringatan milik talent ini.'],
        ['ditolak', 'Dokumen Surat Peringatan yang ditolak tidak dapat dipakai.'],
        ['sudah dipakai', 'Dokumen Surat Peringatan ini sudah dipakai untuk SP lain.'],
    ]);

    it('hanya untuk penempatan Aktif (Belum Aktif & Selesai ditolak 422)', function (array $atribut) {
        $placement = Placement::factory()->create($atribut);
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'hasil_monitoring' => 'X'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Monitoring hanya dapat dicatat untuk penempatan yang sedang aktif.');
    })->with([
        'belum aktif' => [['tanggal_mulai' => now()->addWeek()->toDateString()]],
        'selesai' => [['status_placement' => StatusPlacement::Selesai]],
    ]);

    it('menolak data tidak valid (422)', function () {
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/monitoring', ['id_placement' => 999, 'tipe' => 'teguran', 'tanggal_monitoring' => now()->addDay()->toDateTimeString()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_placement', 'tipe', 'hasil_monitoring', 'tanggal_monitoring' => 'Tanggal monitoring tidak boleh di masa depan.']);
    });

    it('AM, TS, dan talent tidak bisa mencatat (403)', function (Role $role) {
        $placement = Placement::factory()->create();
        $role === Role::Talent ? loginTalentPlacement($placement) : loginSebagai($role);

        $this->postJson('/api/v1/monitoring', ['id_placement' => $placement->id_placement, 'hasil_monitoring' => 'X'])->assertForbidden();
        expect(MonitoringTalent::count())->toBe(0);
    })->with([Role::AccountManager, Role::TalentSupport, Role::Talent]);
});

describe('GET /monitoring (linimasa)', function () {
    it('AM, TS, dan Manager melihat terbaru dulu, bisa difilter placement & tipe dan dicari nama talent', function (Role $role) {
        $this->freezeTime();
        $budi = Placement::factory()->for(Talent::factory()->lengkap()->create(['nama_lengkap' => 'Budi Monitor']))->create();
        MonitoringTalent::factory()->for($budi)->create(['tanggal_monitoring' => now()->subDays(2)]);
        MonitoringTalent::factory()->for($budi)->create(['tanggal_monitoring' => now(), 'tipe' => 'sp', 'id_dokumen' => dokumenSp($budi)->id_dokumen]);
        MonitoringTalent::factory()->create();
        loginSebagai($role);

        $this->getJson('/api/v1/monitoring')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson("/api/v1/monitoring?id_placement={$budi->id_placement}")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.tanggal_monitoring', now()->toIso8601String())
            ->assertJsonPath('data.0.label_tipe', 'SP 1')
            ->assertJsonPath('data.0.dokumen.nama_file', fn ($n) => is_string($n));
        $this->getJson("/api/v1/monitoring?id_placement={$budi->id_placement}&tipe=catatan")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/monitoring?search=budi monitor')->assertJsonCount(2, 'data');
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('HR Client hanya melihat monitoring talent di perusahaannya', function () {
        $milik = Placement::factory()->create();
        MonitoringTalent::factory()->for($milik)->create();
        MonitoringTalent::factory()->create();
        loginHrKlien($milik);

        $this->getJson('/api/v1/monitoring')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.placement.id_placement', $milik->id_placement);
    });

    it('FR-15: talent hanya melihat riwayat miliknya (read-only)', function () {
        $user = loginSebagai(Role::Talent);
        $milik = Placement::factory()->for($user->talent)->create();
        MonitoringTalent::factory()->for($milik)->create(['hasil_monitoring' => 'Kinerja baik.', 'catatan_tindak_lanjut' => 'Pertahankan.']);
        MonitoringTalent::factory()->for(Placement::factory()->create())->create();

        $this->getJson('/api/v1/monitoring')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.hasil_monitoring', 'Kinerja baik.')
            ->assertJsonPath('data.0.placement.id_placement', $milik->id_placement);

        $lain = Placement::factory()->create();
        MonitoringTalent::factory()->for($lain)->create();
        $this->getJson("/api/v1/monitoring?id_placement={$lain->id_placement}")->assertOk()->assertJsonCount(0, 'data');
    });
});

describe('GET /monitoring/talent (kartu per talent)', function () {
    it('menampilkan kartu per penempatan dengan status kontrak, sisa hari, jumlah catatan & SP, dan filter', function () {
        $this->freezeTime();
        $aktif = Placement::factory()->for(Talent::factory()->lengkap()->create(['nama_lengkap' => 'Ani Aktif']))->create([
            'tanggal_mulai' => now()->subMonth()->toDateString(), 'tanggal_selesai' => now()->addDays(30)->toDateString(),
        ]);
        MonitoringTalent::factory()->for($aktif)->count(2)->create();
        MonitoringTalent::factory()->for($aktif)->create(['tipe' => 'sp', 'id_dokumen' => dokumenSp($aktif)->id_dokumen]);
        $belum = Placement::factory()->create(['tanggal_mulai' => now()->addDays(5)->toDateString(), 'tanggal_selesai' => now()->addYear()->toDateString()]);
        Placement::factory()->create(['status_placement' => StatusPlacement::Selesai]);
        loginSebagai(Role::AccountManager);

        $this->getJson('/api/v1/monitoring/talent')->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.ringkasan', ['belum_aktif' => 1, 'aktif' => 1, 'selesai' => 1]);
        $this->getJson('/api/v1/monitoring/talent?status=aktif')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.talent.nama_lengkap', 'Ani Aktif')
            ->assertJsonPath('data.0.status_tampil', 'aktif')
            ->assertJsonPath('data.0.label_status_tampil', 'Aktif')
            ->assertJsonPath('data.0.sisa_hari', 30)
            ->assertJsonPath('data.0.jumlah_catatan', 2)
            ->assertJsonPath('data.0.jumlah_sp', 1);
        $this->getJson('/api/v1/monitoring/talent?status=belum_aktif')
            ->assertJsonPath('data.0.id_placement', $belum->id_placement)
            ->assertJsonPath('data.0.hari_menuju_mulai', 5);
        $this->getJson('/api/v1/monitoring/talent?search=ani')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/monitoring/talent?id_klien={$aktif->id_klien}")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/monitoring/talent?status=lainnya')->assertUnprocessable();
    });

    it('HR Client hanya kliennya (id_klien diabaikan), talent hanya miliknya', function () {
        $milik = Placement::factory()->create();
        $lain = Placement::factory()->create();
        loginHrKlien($milik);
        $this->getJson("/api/v1/monitoring/talent?id_klien={$lain->id_klien}")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_placement', $milik->id_placement);

        loginTalentPlacement($lain);
        $this->getJson('/api/v1/monitoring/talent')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_placement', $lain->id_placement);
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
