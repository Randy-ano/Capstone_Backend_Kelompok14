<?php

use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusPlacement;
use App\Models\PengajuanCuti;
use App\Models\Placement;
use App\Services\CutiService;
use Illuminate\Support\Carbon;

// Jam tetap Senin 2 November 2026 agar perhitungan hari kerja tidak bergantung pada hari test dijalankan.
beforeEach(fn () => $this->travelTo(Carbon::parse('2026-11-02 09:00')));

function dataCuti(Placement $placement, array $ubah = []): array
{
    return [
        'id_placement' => $placement->id_placement,
        'tanggal_mulai' => '2026-11-04', // Rabu
        'tanggal_selesai' => '2026-11-05', // Kamis
        'alasan_cuti' => 'Acara keluarga.',
        ...$ubah,
    ];
}

describe('GET /cuti', function () {
    it('talent hanya melihat cuti miliknya, lengkap dengan jumlah hari kerja', function () {
        $placement = Placement::factory()->create();
        // Jumat–Selasa: 5 hari kalender, 3 hari kerja.
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-06', 'tanggal_selesai' => '2026-11-10']);
        PengajuanCuti::factory()->create();
        loginTalentPlacement($placement);

        $this->getJson('/api/v1/cuti')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.placement.id_placement', $placement->id_placement)
            ->assertJsonPath('data.0.jumlah_hari', 5)
            ->assertJsonPath('data.0.jumlah_hari_kerja', 3)
            ->assertJsonMissingPath('data.0.approval_manager')
            ->assertJsonStructure(['data' => [[
                'id_cuti', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_hari', 'jumlah_hari_kerja', 'alasan_cuti', 'status_cuti',
                'approval_klien' => ['status', 'oleh'], 'diajukan_pada',
                'placement' => ['id_placement', 'posisi_penempatan', 'status_placement', 'status_tampil', 'label_status_tampil', 'talent', 'klien'],
            ]], 'links', 'meta']);
    });

    it('HR Client melihat cuti talent di kliennya; Manager, AM, dan TS melihat semua dan bisa memfilter status', function () {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create(['status_cuti' => StatusCuti::Dibatalkan]);
        PengajuanCuti::factory()->count(2)->create();

        loginHrKlien($placement);
        $this->getJson('/api/v1/cuti')->assertJsonCount(1, 'data');

        foreach ([Role::Manager, Role::AccountManager, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->getJson('/api/v1/cuti')->assertOk()->assertJsonCount(3, 'data');
            $this->getJson('/api/v1/cuti?status=dibatalkan')->assertJsonCount(1, 'data');
        }
    });

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/cuti?status=libur')->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });
});

describe('talent belum ditempatkan', function () {
    it('ditolak dari cuti dan dokumen (403), tetapi tetap bisa memakai profil & CV', function () {
        loginSebagai(Role::Talent);
        $pesan = ['message' => 'Fitur ini tersedia setelah Anda ditempatkan di perusahaan klien.'];

        $this->getJson('/api/v1/cuti')->assertForbidden()->assertExactJson($pesan);
        $this->getJson('/api/v1/cuti/kuota')->assertForbidden()->assertExactJson($pesan);
        $this->postJson('/api/v1/cuti', [])->assertForbidden()->assertExactJson($pesan);
        $this->getJson('/api/v1/dokumen')->assertForbidden()->assertExactJson($pesan);
        $this->getJson('/api/v1/talent/profil')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.sudah_ditempatkan', false);
    });

    it('penempatan selesai, dihentikan, atau lewat tanggal selesai menutup akses; penempatan belum mulai tetap membuka akses', function (array $atribut, bool $boleh) {
        $placement = Placement::factory()->create($atribut);
        loginTalentPlacement($placement);

        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.sudah_ditempatkan', $boleh);
        $this->getJson('/api/v1/cuti')->assertStatus($boleh ? 200 : 403);
        $this->getJson('/api/v1/dokumen')->assertStatus($boleh ? 200 : 403);
    })->with([
        'aktif' => [[], true],
        'belum aktif (mulai bulan depan)' => [['tanggal_mulai' => '2026-12-01'], true],
        'selesai' => [['status_placement' => StatusPlacement::Selesai], false],
        'dihentikan' => [['status_placement' => StatusPlacement::Dihentikan], false],
        'tanggal selesai lewat' => [['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-10-31'], false],
    ]);

    it('role selain talent tidak membawa sudah_ditempatkan', function () {
        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonMissingPath('data.sudah_ditempatkan');
    });
});

describe('GET /cuti/kuota', function () {
    it('menghitung hari kerja cuti menunggu + disetujui di tahun berjalan', function () {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-06', 'tanggal_selesai' => '2026-11-10']); // 3, menunggu
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-16', 'tanggal_selesai' => '2026-11-17', 'status_cuti' => StatusCuti::Disetujui]); // 2
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-18', 'tanggal_selesai' => '2026-11-18', 'status_cuti' => StatusCuti::Ditolak]);
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-19', 'tanggal_selesai' => '2026-11-19', 'status_cuti' => StatusCuti::Dibatalkan]);
        loginTalentPlacement($placement);

        $this->getJson('/api/v1/cuti/kuota')
            ->assertOk()
            ->assertExactJson(['data' => ['tahun' => 2026, 'kuota' => CutiService::KUOTA_HARI_KERJA_PER_TAHUN, 'terpakai' => 5, 'sisa' => 7], 'message' => 'Berhasil']);
        $this->getJson('/api/v1/cuti/kuota?tahun=2027')->assertJsonPath('data.terpakai', 0);
        $this->getJson('/api/v1/cuti/kuota?tahun=abc')->assertUnprocessable()->assertJsonValidationErrors(['tahun']);
    });

    it('khusus talent (403)', function (Role $role) {
        loginSebagai($role);
        $this->getJson('/api/v1/cuti/kuota')->assertForbidden();
    })->with(roleSelain(Role::Talent));
});

describe('POST /cuti', function () {
    it('talent mengajukan cuti untuk placement aktif miliknya', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement))
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan cuti berhasil dikirim.')
            ->assertJsonPath('data.status_cuti', 'menunggu')
            ->assertJsonPath('data.jumlah_hari_kerja', 2)
            ->assertJsonPath('data.approval_klien.status', 'menunggu');

        expect(PengajuanCuti::sole()->id_talent)->toBe($placement->id_talent);
    });

    it('menghitung hari kerja saja untuk rentang yang melewati akhir pekan, dan boleh mulai besok', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-06', 'tanggal_selesai' => '2026-11-10']))
            ->assertCreated()
            ->assertJsonPath('data.jumlah_hari', 5)
            ->assertJsonPath('data.jumlah_hari_kerja', 3);
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-03', 'tanggal_selesai' => '2026-11-03']))
            ->assertCreated();
        $this->getJson('/api/v1/cuti/kuota')->assertJsonPath('data.sisa', 8);
    });

    it('menolak data tidak valid (422)', function (Closure $ubah, array $kolom) {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', $ubah($placement))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($kolom);
    })->with([
        'kosong' => [fn () => [], ['id_placement', 'tanggal_mulai', 'tanggal_selesai', 'alasan_cuti']],
        'mulai hari ini' => [fn ($p) => dataCuti($p, ['tanggal_mulai' => '2026-11-02']), ['tanggal_mulai']],
        'selesai sebelum mulai' => [fn ($p) => dataCuti($p, ['tanggal_selesai' => '2026-11-03']), ['tanggal_selesai']],
    ]);

    it('menolak tanggal mulai hari ini dengan pesan paling cepat besok', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-02']))
            ->assertJsonPath('errors.tanggal_mulai.0', 'Tanggal mulai cuti paling cepat besok.');
    });

    it('menolak rentang yang hanya berisi Sabtu/Minggu', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-07', 'tanggal_selesai' => '2026-11-08']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_mulai.0', 'Rentang tanggal cuti hanya berisi hari Sabtu/Minggu. Pilih minimal satu hari kerja.');
    });

    it('menolak tanggal di luar periode penempatan', function () {
        $placement = Placement::factory()->create(['tanggal_mulai' => '2026-11-10', 'tanggal_selesai' => '2026-11-20']);
        loginTalentPlacement($placement);
        $pesan = 'Tanggal cuti harus dalam periode penempatan (2026-11-10 s.d. 2026-11-20).';

        $this->postJson('/api/v1/cuti', dataCuti($placement))
            ->assertUnprocessable()->assertJsonPath('errors.tanggal_mulai.0', $pesan);
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-19', 'tanggal_selesai' => '2026-11-23']))
            ->assertUnprocessable()->assertJsonPath('errors.tanggal_mulai.0', $pesan);
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-19', 'tanggal_selesai' => '2026-11-20']))
            ->assertCreated();
    });

    it('menolak pengajuan yang melebihi sisa kuota, dengan pesan berisi sisa kuota', function () {
        $placement = Placement::factory()->create();
        // 9–20 November: 10 hari kerja disetujui → sisa 2.
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-09', 'tanggal_selesai' => '2026-11-20', 'status_cuti' => StatusCuti::Disetujui]);
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-23', 'tanggal_selesai' => '2026-11-25']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_selesai.0', 'Sisa kuota cuti tahun 2026 adalah 2 hari kerja, sedangkan pengajuan ini 3 hari kerja.');
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-11-23', 'tanggal_selesai' => '2026-11-24']))
            ->assertCreated();
    });

    it('menghitung kuota per tahun kalender untuk cuti yang melewati pergantian tahun', function () {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create(['tanggal_mulai' => '2026-11-09', 'tanggal_selesai' => '2026-11-24', 'status_cuti' => StatusCuti::Disetujui]); // 12
        loginTalentPlacement($placement);

        // 31 Des 2026 (Kamis) masih tahun 2026 yang kuotanya habis.
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2026-12-31', 'tanggal_selesai' => '2027-01-04']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_selesai.0', 'Sisa kuota cuti tahun 2026 adalah 0 hari kerja, sedangkan pengajuan ini 1 hari kerja.');
        $this->postJson('/api/v1/cuti', dataCuti($placement, ['tanggal_mulai' => '2027-01-01', 'tanggal_selesai' => '2027-01-04']))
            ->assertCreated()
            ->assertJsonPath('data.jumlah_hari_kerja', 2);
    });

    it('menolak placement milik talent lain atau yang tidak aktif (422)', function () {
        $placement = Placement::factory()->create();
        $lain = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($lain))
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Cuti hanya dapat diajukan untuk penempatan milik Anda.');

        // Penempatan lain yang masih berjalan menjaga akses, tetapi cuti untuk penempatan selesai ditolak.
        $selesai = Placement::factory()->create(['id_talent' => $placement->id_talent, 'status_placement' => StatusPlacement::Selesai]);
        $this->postJson('/api/v1/cuti', dataCuti($selesai))
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Cuti hanya dapat diajukan untuk penempatan yang masih aktif.');
    });

    it('menolak tanggal yang tumpang tindih dengan cuti menunggu/disetujui, tapi tidak dengan yang ditolak/dibatalkan', function (StatusCuti $status, bool $ditolak) {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create([
            'tanggal_mulai' => '2026-11-05',
            'tanggal_selesai' => '2026-11-09',
            'status_cuti' => $status,
        ]);
        loginTalentPlacement($placement);

        $respons = $this->postJson('/api/v1/cuti', dataCuti($placement));

        $ditolak
            ? $respons->assertUnprocessable()->assertJsonPath('errors.tanggal_mulai.0', 'Tanggal cuti bertumpang tindih dengan cuti lain yang masih menunggu atau sudah disetujui.')
            : $respons->assertCreated();
    })->with([
        'menunggu' => [StatusCuti::Menunggu, true],
        'disetujui' => [StatusCuti::Disetujui, true],
        'ditolak' => [StatusCuti::Ditolak, false],
        'dibatalkan' => [StatusCuti::Dibatalkan, false],
    ]);

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/cuti', dataCuti(Placement::factory()->create()))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Talent));
});

describe('PATCH /cuti/{id}/approval (R21: hanya HR Client)', function () {
    it('keputusan HR Client langsung menjadi status akhir: disetujui', function () {
        $cuti = PengajuanCuti::factory()->create();

        $hr = loginHrKlien($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertOk()
            ->assertJsonPath('message', 'Cuti disetujui.')
            ->assertJsonPath('data.status_cuti', 'disetujui')
            ->assertJsonPath('data.approval_klien.status', 'disetujui')
            ->assertJsonPath('data.approval_klien.oleh', $hr->hrClient->nama_hr);

        expect($cuti->fresh())
            ->status_approval_manager->toBe(StatusApproval::Menunggu)
            ->id_approver_manager->toBeNull();
    });

    it('keputusan HR Client langsung menjadi status akhir: ditolak, lalu final', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginHrKlien($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'ditolak'])
            ->assertOk()
            ->assertJsonPath('message', 'Cuti ditolak.')
            ->assertJsonPath('data.status_cuti', 'ditolak');

        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.keputusan.0', 'Cuti ini sudah ditolak; keputusan tidak dapat diubah.');
    });

    it('menolak keputusan tidak valid (422)', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginHrKlien($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'menunggu'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['keputusan']);
    });

    it('menolak Manager, AM, TS, talent (403) dan HR Client dari klien lain', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginSebagai(Role::HrClient);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan cuti ini.']);

        foreach ([Role::Manager, Role::AccountManager, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
                ->assertForbidden()
                ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
        }
        loginTalentPlacement($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])->assertForbidden();

        expect($cuti->fresh()->status_cuti)->toBe(StatusCuti::Menunggu);
    });
});

describe('PATCH /cuti/{id}/batal', function () {
    it('talent membatalkan cutinya selama masih menunggu', function () {
        $cuti = PengajuanCuti::factory()->create();
        loginTalentPlacement($cuti->placement);

        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")
            ->assertOk()
            ->assertJsonPath('message', 'Pengajuan cuti dibatalkan.')
            ->assertJsonPath('data.status_cuti', 'dibatalkan');

        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_cuti.0', 'Hanya cuti yang masih menunggu yang dapat dibatalkan.');
    });

    it('menolak talent lain dan role selain talent (403)', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginTalentPlacement(Placement::factory()->create());
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda hanya dapat membatalkan cuti milik Anda.']);

        loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")->assertForbidden();

        expect($cuti->fresh()->status_cuti)->toBe(StatusCuti::Menunggu);
    });

    it('membalas 404 jika cuti tidak ada', function () {
        loginTalentPlacement(Placement::factory()->create());

        $this->patchJson('/api/v1/cuti/999/batal')->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});
