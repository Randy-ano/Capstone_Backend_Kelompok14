<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusPengajuan;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\PengajuanKandidat;
use App\Models\ScreeningTalent;

describe('GET /pengajuan', function () {
    it('internal melihat semua pengajuan beserta struktur lengkap', function (Role $role) {
        loginSebagai($role);
        rekrutmenPengajuan(rekrutmenJob());
        rekrutmenPengajuan(rekrutmenJob());

        $this->getJson('/api/v1/pengajuan')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id_pengajuan', 'tanggal_pengajuan', 'status_pengajuan',
                'talent' => ['id_talent', 'nama_lengkap', 'email', 'pendidikan', 'status_kandidat'],
                'job_requirement' => ['id_job_req', 'posisi', 'status_req', 'klien' => ['id_klien', 'nama_perusahaan']],
                'account_manager' => ['id_pegawai', 'nama_pegawai'],
                'screening' => ['id_screening', 'skor_kecocokan', 'catatan_screening'],
                'jumlah_interview',
            ]], 'links', 'meta']);
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('HR Client hanya melihat pengajuan ke job kliennya', function () {
        $hr = loginSebagai(Role::HrClient);
        $milik = rekrutmenPengajuan(rekrutmenJob($hr->idKlien()));
        rekrutmenPengajuan(rekrutmenJob());

        $this->getJson('/api/v1/pengajuan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_pengajuan', $milik->id_pengajuan);
    });

    it('talent hanya melihat pengajuan miliknya tanpa catatan screening', function () {
        $user = loginSebagai(Role::Talent);
        $milik = rekrutmenPengajuan(rekrutmenJob(), talent: $user->talent);
        rekrutmenPengajuan(rekrutmenJob());

        $this->getJson('/api/v1/pengajuan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_pengajuan', $milik->id_pengajuan)
            ->assertJsonMissingPath('data.0.screening');
    });

    it('bisa difilter status, job, dan dicari nama talent', function () {
        loginSebagai(Role::AccountManager);
        $job = rekrutmenJob();
        rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);
        $cari = rekrutmenPengajuan(rekrutmenJob());
        $cari->talent->update(['nama_lengkap' => 'Sinta Dicari']);

        $this->getJson('/api/v1/pengajuan?status=lanjut_interview')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/pengajuan?id_job_req={$job->id_job_req}")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/pengajuan?search=sinta')->assertJsonCount(1, 'data')->assertJsonPath('data.0.talent.nama_lengkap', 'Sinta Dicari');
        $this->getJson('/api/v1/pengajuan?status=batal')->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });
});

describe('GET /pengajuan/{id}', function () {
    it('menampilkan detail dengan interview dan evaluasi', function () {
        loginSebagai(Role::Manager);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview);
        rekrutmenInterview($pengajuan);

        $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")
            ->assertOk()
            ->assertJsonCount(1, 'data.interviews')
            ->assertJsonPath('data.evaluasi', null)
            ->assertJsonPath('data.id_placement', null);
    });

    it('menolak HR Client klien lain dan talent lain (403)', function (Role $role) {
        loginSebagai($role);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob());

        $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan ini.']);
    })->with([Role::HrClient, Role::Talent]);
});

describe('POST /pengajuan', function () {
    it('AM mengajukan screening lolos; talent & job disalin dan progres dicatat (R14, R12)', function () {
        $am = loginSebagai(Role::AccountManager);
        $job = rekrutmenJob();
        $screening = rekrutmenScreeningLolos($job);

        $this->postJson('/api/v1/pengajuan', ['id_screening' => $screening->id_screening])
            ->assertCreated()
            ->assertJsonPath('message', 'Kandidat berhasil diajukan ke klien.')
            ->assertJsonPath('data.status_pengajuan', 'diajukan')
            ->assertJsonPath('data.talent.id_talent', $screening->id_talent)
            ->assertJsonPath('data.job_requirement.id_job_req', $job->id_job_req)
            ->assertJsonPath('data.account_manager.id_pegawai', $am->pegawai->id_pegawai);

        expect($job->progres()->latest('id_progres')->first())
            ->status_tahapan->toBe(StatusTahapan::Diajukan)
            ->id_talent->toBe($screening->id_talent)
            ->id_penanggung_jawab->toBe($am->pegawai->id_pegawai);
    });

    it('menolak screening belum lolos, sudah diajukan, atau job tidak diproses (422)', function (string $kasus, string $pesan) {
        loginSebagai(Role::AccountManager);
        $screening = match ($kasus) {
            'pending' => ScreeningTalent::factory()->for(rekrutmenJob())->create(['hasil_screening' => HasilScreening::Pending]),
            'sudah diajukan' => rekrutmenPengajuan(rekrutmenJob())->screening,
            'job terpenuhi' => rekrutmenScreeningLolos(rekrutmenJob(atribut: ['status_req' => StatusReq::Terpenuhi])),
        };

        $this->postJson('/api/v1/pengajuan', ['id_screening' => $screening->id_screening])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_screening.0', $pesan);
    })->with([
        ['pending', 'Hanya kandidat yang lolos screening yang dapat diajukan ke klien.'],
        ['sudah diajukan', 'Kandidat ini sudah diajukan ke klien.'],
        ['job terpenuhi', 'Job requirement berstatus terpenuhi; proses kandidat tidak dapat dilanjutkan.'],
    ]);

    it('menolak body tidak valid (422)', function () {
        loginSebagai(Role::AccountManager);

        $this->postJson('/api/v1/pengajuan', ['id_screening' => 999])->assertUnprocessable()->assertJsonValidationErrors(['id_screening']);
    });

    it('menolak selain Account Manager (403)', function (Role $role) {
        loginSebagai($role);
        $screening = rekrutmenScreeningLolos(rekrutmenJob());

        $this->postJson('/api/v1/pengajuan', ['id_screening' => $screening->id_screening])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(PengajuanKandidat::count())->toBe(0);
    })->with(roleSelain(Role::AccountManager));
});

describe('PATCH /pengajuan/{id}/status', function () {
    it('HR Client melanjutkan ke interview', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($hr->idKlien()));

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'lanjut_interview'])
            ->assertOk()
            ->assertJsonPath('message', 'Kandidat dilanjutkan ke tahap interview.')
            ->assertJsonPath('data.status_pengajuan', 'lanjut_interview');

        expect($pengajuan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::DalamProses);
    });

    it('HR Client menolak: talent kembali tersedia dan progres ditolak dicatat (R15, R12)', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($hr->idKlien()));

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'ditolak'])
            ->assertOk()
            ->assertJsonPath('message', 'Kandidat ditolak.');

        expect($pengajuan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($pengajuan->jobRequirement->progres()->latest('id_progres')->first()->status_tahapan)->toBe(StatusTahapan::Ditolak);
    });

    it('menolak peninjauan ulang dan status tidak dikenal (422)', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($hr->idKlien()), StatusPengajuan::LanjutInterview);

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'ditolak'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_pengajuan.0', 'Pengajuan ini sudah ditinjau.');

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'diajukan'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_pengajuan']);
    });

    it('menolak HR Client klien lain (403)', function () {
        loginSebagai(Role::HrClient);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob());

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'ditolak'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan ini.']);

        expect($pengajuan->fresh()->status_pengajuan)->toBe(StatusPengajuan::Diajukan);
    });

    it('menolak selain HR Client (403)', function (Role $role) {
        loginSebagai($role);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob());

        $this->patchJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}/status", ['status_pengajuan' => 'ditolak'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::HrClient));
});
