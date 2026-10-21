<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\Kompetensi;
use App\Models\PengajuanKandidat;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\TalentPool;

/** Job diajukan + talent pool tersedia yang cocok 1 dari 2 kompetensi. */
function siapScreening(StatusReq $status = StatusReq::Diajukan): array
{
    [$a, $b] = Kompetensi::factory()->count(2)->create();
    $job = JobRequirement::factory()->create(['status_req' => $status, 'pengalaman_min' => 0]);
    $job->kompetensi()->attach([$a->id_kompetensi, $b->id_kompetensi]);
    $talent = Talent::factory()->lengkap()->create();
    $talent->kompetensi()->attach($a);
    TalentPool::factory()->for($talent)->create();

    return [$job, $talent];
}

describe('GET /screening', function () {
    it('menampilkan daftar screening untuk TS, AM, dan Manager', function (Role $role) {
        loginSebagai($role);
        ScreeningTalent::factory()->count(2)->create();

        $this->getJson('/api/v1/screening')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [[
                'id_screening', 'skor_kecocokan', 'tanggal_screening', 'catatan_screening', 'hasil_screening',
                'talent' => ['id_talent', 'nama_lengkap', 'email', 'status_kandidat'],
                'job_requirement' => ['id_job_req', 'posisi', 'status_req', 'klien' => ['id_klien', 'nama_perusahaan']],
                'talent_support' => ['id_pegawai', 'nama_pegawai'],
            ]], 'links', 'meta']);
    })->with([Role::TalentSupport, Role::AccountManager, Role::Manager]);

    it('bisa difilter job, talent, hasil, dan dicari nama talent', function () {
        loginSebagai(Role::TalentSupport);
        $job = JobRequirement::factory()->create();
        $budi = Talent::factory()->lengkap()->create(['nama_lengkap' => 'Budi Cari']);
        ScreeningTalent::factory()->for($job)->for($budi)->lolos()->create();
        ScreeningTalent::factory()->for($job)->create();
        ScreeningTalent::factory()->create();

        $this->getJson("/api/v1/screening?id_job_req={$job->id_job_req}")->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/screening?hasil=lolos')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/screening?id_talent={$budi->id_talent}")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/screening?search=budi cari')->assertJsonCount(1, 'data')->assertJsonPath('data.0.talent.nama_lengkap', 'Budi Cari');
    });

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::TalentSupport);

        $this->getJson('/api/v1/screening?hasil=gagal&id_job_req=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hasil', 'id_job_req']);
    });

    it('menolak talent dan HR Client (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/screening')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with([Role::Talent, Role::HrClient]);
});

describe('POST /screening', function () {
    it('membuat screening dengan skor R9 dan menjalankan R10 serta log progres R12', function () {
        $ts = loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapScreening();

        $this->postJson('/api/v1/screening', [
            'id_job_req' => $job->id_job_req,
            'id_talent' => $talent->id_talent,
            'catatan_screening' => 'Cocok.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Screening berhasil dibuat.')
            ->assertJsonPath('data.skor_kecocokan', 60)
            ->assertJsonPath('data.hasil_screening', 'pending')
            ->assertJsonPath('data.talent.status_kandidat', 'dalam_proses')
            ->assertJsonPath('data.job_requirement.status_req', 'diproses')
            ->assertJsonPath('data.talent_support.id_pegawai', $ts->pegawai->id_pegawai);

        // Log 1: kebutuhan_dibuat (R8, saat job diajukan); log 2: screening (R12).
        $log = $job->progres()->orderBy('id_progres')->get();
        expect($talent->fresh()->status_kandidat)->toBe(StatusKandidat::DalamProses)
            ->and($job->fresh()->status_req)->toBe(StatusReq::Diproses)
            ->and($log->pluck('status_tahapan')->all())->toBe([StatusTahapan::KebutuhanDibuat, StatusTahapan::Screening])
            ->and($log->last())
            ->status_tahapan->toBe(StatusTahapan::Screening)
            ->id_talent->toBe($talent->id_talent)
            ->id_penanggung_jawab->toBe($ts->pegawai->id_pegawai);
    });

    it('tidak mengubah status job yang sudah diproses', function () {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapScreening(StatusReq::Diproses);

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])->assertCreated();

        expect($job->fresh()->status_req)->toBe(StatusReq::Diproses);
    });

    it('menolak screening ganda untuk job yang sama (422)', function () {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapScreening();
        ScreeningTalent::factory()->for($job)->for($talent)->create(['hasil_screening' => HasilScreening::TidakLolos]);

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_talent.0', 'Talent sudah pernah di-screening untuk job requirement ini.');
    });

    it('menolak talent yang tidak tersedia (422)', function (string $kasus) {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapScreening();
        match ($kasus) {
            'dalam proses' => $talent->update(['status_kandidat' => StatusKandidat::DalamProses]),
            'pool tidak tersedia' => $talent->talentPool->update(['status_ketersediaan' => StatusKetersediaan::TidakTersedia]),
            'di luar pool' => $talent->talentPool->delete(),
        };

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_talent']);

        expect(ScreeningTalent::count())->toBe(0);
    })->with(['dalam proses', 'pool tidak tersedia', 'di luar pool']);

    it('menolak job yang belum diajukan atau sudah selesai (422)', function (StatusReq $status) {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapScreening($status);

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_job_req']);
    })->with([StatusReq::Draft, StatusReq::Terpenuhi, StatusReq::Dibatalkan]);

    it('menolak body tidak valid (422)', function () {
        loginSebagai(Role::TalentSupport);

        $this->postJson('/api/v1/screening', ['id_job_req' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_job_req', 'id_talent']);
    });

    it('menolak selain Talent Support (403)', function (Role $role) {
        loginSebagai($role);
        [$job, $talent] = siapScreening();

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(ScreeningTalent::count())->toBe(0);
    })->with(roleSelain(Role::TalentSupport));
});

describe('PUT /screening/{id}', function () {
    it('mengubah hasil menjadi lolos tanpa mengubah status talent', function () {
        loginSebagai(Role::TalentSupport);
        $talent = Talent::factory()->lengkap()->create(['status_kandidat' => StatusKandidat::DalamProses]);
        $screening = ScreeningTalent::factory()->for($talent)->create();

        $this->putJson("/api/v1/screening/{$screening->id_screening}", ['hasil_screening' => 'lolos', 'catatan_screening' => 'Bagus.'])
            ->assertOk()
            ->assertJsonPath('message', 'Screening berhasil diperbarui.')
            ->assertJsonPath('data.hasil_screening', 'lolos')
            ->assertJsonPath('data.catatan_screening', 'Bagus.');

        expect($talent->fresh()->status_kandidat)->toBe(StatusKandidat::DalamProses);
    });

    it('hasil tidak lolos mengembalikan talent tersedia (R11) dan mencatat progres ditolak (R12)', function () {
        loginSebagai(Role::TalentSupport);
        $talent = Talent::factory()->lengkap()->create(['status_kandidat' => StatusKandidat::DalamProses]);
        $screening = ScreeningTalent::factory()->for($talent)->create();

        $this->putJson("/api/v1/screening/{$screening->id_screening}", ['hasil_screening' => 'tidak_lolos'])->assertOk();

        expect($talent->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($screening->jobRequirement->progres()->latest('id_progres')->first())
            ->status_tahapan->toBe(StatusTahapan::Ditolak)
            ->id_talent->toBe($talent->id_talent);

        // Mengubah catatan saja tidak membuat log baru.
        $jumlahLog = $screening->jobRequirement->progres()->count();
        $this->putJson("/api/v1/screening/{$screening->id_screening}", ['catatan_screening' => 'Revisi catatan.'])->assertOk();
        expect($screening->jobRequirement->progres()->count())->toBe($jumlahLog);
    });

    it('menolak perubahan hasil tidak lolos, screening yang sudah diajukan, dan job yang sudah selesai (422)', function (string $kasus, string $pesan) {
        loginSebagai(Role::TalentSupport);
        $screening = match ($kasus) {
            'tidak lolos final' => ScreeningTalent::factory()->create(['hasil_screening' => HasilScreening::TidakLolos]),
            'sudah diajukan' => PengajuanKandidat::factory()->create()->screening,
            'job dibatalkan' => ScreeningTalent::factory()->for(JobRequirement::factory()->state(['status_req' => StatusReq::Dibatalkan]))->create(),
        };

        $this->putJson("/api/v1/screening/{$screening->id_screening}", ['hasil_screening' => 'pending'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.hasil_screening.0', $pesan);
    })->with([
        ['tidak lolos final', 'Hasil screening tidak lolos tidak dapat diubah lagi.'],
        ['sudah diajukan', 'Screening yang sudah diajukan ke klien tidak dapat diubah.'],
        ['job dibatalkan', 'Job requirement sudah dibatalkan; screening tidak dapat diubah.'],
    ]);

    it('menolak body kosong atau hasil tidak dikenal (422)', function (array $body) {
        loginSebagai(Role::TalentSupport);
        $screening = ScreeningTalent::factory()->create();

        $this->putJson("/api/v1/screening/{$screening->id_screening}", $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hasil_screening']);
    })->with([[[]], [['hasil_screening' => 'gagal']]]);

    it('menolak selain Talent Support (403)', function (Role $role) {
        loginSebagai($role);
        $screening = ScreeningTalent::factory()->create();

        $this->putJson("/api/v1/screening/{$screening->id_screening}", ['hasil_screening' => 'lolos'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect($screening->fresh()->hasil_screening)->toBe(HasilScreening::Pending);
    })->with(roleSelain(Role::TalentSupport));

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::TalentSupport);

        $this->putJson('/api/v1/screening/999', ['hasil_screening' => 'lolos'])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});
