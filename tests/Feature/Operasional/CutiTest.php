<?php

use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusPlacement;
use App\Models\PengajuanCuti;
use App\Models\Placement;

function dataCuti(Placement $placement, array $ubah = []): array
{
    return [
        'id_placement' => $placement->id_placement,
        'tanggal_mulai' => now()->addDays(5)->toDateString(),
        'tanggal_selesai' => now()->addDays(6)->toDateString(),
        'alasan_cuti' => 'Acara keluarga.',
        ...$ubah,
    ];
}

describe('GET /cuti', function () {
    it('talent hanya melihat cuti miliknya', function () {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create();
        PengajuanCuti::factory()->create();
        loginTalentPlacement($placement);

        $this->getJson('/api/v1/cuti')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.placement.id_placement', $placement->id_placement)
            ->assertJsonPath('data.0.jumlah_hari', 2)
            ->assertJsonStructure(['data' => [[
                'id_cuti', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_hari', 'alasan_cuti', 'status_cuti',
                'approval_klien' => ['status', 'oleh'], 'approval_manager' => ['status', 'oleh'], 'diajukan_pada',
                'placement' => ['id_placement', 'posisi_penempatan', 'status_placement', 'talent', 'klien'],
            ]], 'links', 'meta']);
    });

    it('HR Client melihat cuti talent di kliennya, Manager melihat semua, dan bisa difilter status', function () {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create(['status_cuti' => StatusCuti::Dibatalkan]);
        PengajuanCuti::factory()->count(2)->create();

        loginHrKlien($placement);
        $this->getJson('/api/v1/cuti')->assertJsonCount(1, 'data');

        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/cuti')->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/cuti?status=dibatalkan')->assertJsonCount(1, 'data');
    });

    it('menolak AM dan TS (403) serta filter tidak valid (422)', function () {
        loginSebagai(Role::AccountManager);
        $this->getJson('/api/v1/cuti')->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/cuti?status=libur')->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });
});

describe('POST /cuti', function () {
    it('talent mengajukan cuti untuk placement aktif miliknya', function () {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($placement))
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan cuti berhasil dikirim.')
            ->assertJsonPath('data.status_cuti', 'menunggu')
            ->assertJsonPath('data.approval_klien.status', 'menunggu')
            ->assertJsonPath('data.approval_manager.status', 'menunggu');

        expect(PengajuanCuti::sole()->id_talent)->toBe($placement->id_talent);
    });

    it('menolak data tidak valid (422)', function (Closure $ubah, array $kolom) {
        $placement = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', $ubah($placement))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($kolom);
    })->with([
        'kosong' => [fn () => [], ['id_placement', 'tanggal_mulai', 'tanggal_selesai', 'alasan_cuti']],
        'mulai sebelum hari ini' => [fn ($p) => dataCuti($p, ['tanggal_mulai' => now()->subDay()->toDateString()]), ['tanggal_mulai']],
        'selesai sebelum mulai' => [fn ($p) => dataCuti($p, ['tanggal_selesai' => now()->addDays(4)->toDateString()]), ['tanggal_selesai']],
    ]);

    it('menolak placement milik talent lain atau yang tidak aktif (422)', function () {
        $placement = Placement::factory()->create();
        $lain = Placement::factory()->create();
        loginTalentPlacement($placement);

        $this->postJson('/api/v1/cuti', dataCuti($lain))
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Cuti hanya dapat diajukan untuk placement milik Anda.');

        $placement->update(['status_placement' => StatusPlacement::Selesai]);
        $this->postJson('/api/v1/cuti', dataCuti($placement))
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Cuti hanya dapat diajukan untuk placement yang masih aktif.');
    });

    it('menolak tanggal yang tumpang tindih dengan cuti menunggu/disetujui, tapi tidak dengan yang ditolak/dibatalkan', function (StatusCuti $status, bool $ditolak) {
        $placement = Placement::factory()->create();
        PengajuanCuti::factory()->for($placement)->create([
            'tanggal_mulai' => now()->addDays(6)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
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

describe('PATCH /cuti/{id}/approval (R21)', function () {
    it('disetujui jika HR Client dan Manager sama-sama setuju', function () {
        $cuti = PengajuanCuti::factory()->create();

        $hr = loginHrKlien($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertOk()
            ->assertJsonPath('message', 'Keputusan disimpan. Menunggu keputusan pihak lain.')
            ->assertJsonPath('data.status_cuti', 'menunggu')
            ->assertJsonPath('data.approval_klien.status', 'disetujui')
            ->assertJsonPath('data.approval_klien.oleh', $hr->hrClient->nama_hr);

        $manager = loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertOk()
            ->assertJsonPath('message', 'Cuti disetujui.')
            ->assertJsonPath('data.status_cuti', 'disetujui')
            ->assertJsonPath('data.approval_manager.oleh', $manager->pegawai->nama_pegawai);
    });

    it('ditolak jika salah satu menolak, dan keputusan berikutnya tidak bisa diberikan', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'ditolak'])
            ->assertOk()
            ->assertJsonPath('message', 'Cuti ditolak.')
            ->assertJsonPath('data.status_cuti', 'ditolak');

        loginHrKlien($cuti->placement);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.keputusan.0', 'Cuti ini sudah ditolak; keputusan tidak dapat diubah.');
    });

    it('menolak keputusan ganda dari pihak yang sama dan keputusan tidak valid (422)', function () {
        $cuti = PengajuanCuti::factory()->create(['status_approval_manager' => StatusApproval::Disetujui]);

        loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'ditolak'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.keputusan.0', 'Anda sudah memberikan keputusan untuk cuti ini.');
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'menunggu'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['keputusan']);
    });

    it('menolak HR Client dari klien lain dan role lain (403)', function () {
        $cuti = PengajuanCuti::factory()->create();

        loginSebagai(Role::HrClient);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan cuti ini.']);

        foreach ([Role::Talent, Role::AccountManager, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/approval", ['keputusan' => 'disetujui'])->assertForbidden();
        }

        expect($cuti->fresh()->status_approval_klien)->toBe(StatusApproval::Menunggu);
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

        loginSebagai(Role::Talent);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda hanya dapat membatalkan cuti milik Anda.']);

        loginSebagai(Role::Manager);
        $this->patchJson("/api/v1/cuti/{$cuti->id_cuti}/batal")->assertForbidden();

        expect($cuti->fresh()->status_cuti)->toBe(StatusCuti::Menunggu);
    });

    it('membalas 404 jika cuti tidak ada', function () {
        loginSebagai(Role::Talent);

        $this->patchJson('/api/v1/cuti/999/batal')->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});
