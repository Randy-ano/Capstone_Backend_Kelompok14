<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\HrClient;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\ScreeningTalent;
use App\Models\Talent;

function dataJobBaru(array $ubah = []): array
{
    return [
        'posisi' => 'Backend Developer',
        'jumlah_kebutuhan' => 2,
        'kualifikasi' => 'Menguasai Laravel.',
        'pengalaman_min' => 2,
        'sla_deadline' => now()->addDays(30)->toDateTimeString(),
        'kompetensi' => Kompetensi::factory()->count(2)->create()->modelKeys(),
        ...$ubah,
    ];
}

describe('GET /job-requirements', function () {
    it('menampilkan semua job beserta ringkasan untuk role internal', function (Role $role) {
        loginSebagai($role);
        JobRequirement::factory()->count(2)->hasAttached(Kompetensi::factory(), [], 'kompetensi')->create(['status_req' => StatusReq::Diajukan]);
        JobRequirement::factory()->create(['status_req' => StatusReq::Draft]);
        JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->subDay()]);

        // Urut terbaru dulu: data.2 adalah job pertama yang punya kompetensi.
        $this->getJson('/api/v1/job-requirements')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonCount(1, 'data.2.kompetensi')
            ->assertJsonPath('meta.ringkasan.total', 4)
            ->assertJsonPath('meta.ringkasan.diajukan', 3)
            ->assertJsonPath('meta.ringkasan.draft', 1)
            ->assertJsonPath('meta.ringkasan.sla_terlambat', 1)
            ->assertJsonStructure(['data' => [[
                'id_job_req', 'posisi', 'klien' => ['id_klien', 'nama_perusahaan'], 'account_manager', 'jumlah_kebutuhan',
                'terpenuhi', 'pengalaman_min', 'kualifikasi', 'status_req', 'sla_deadline', 'indikator_sla',
                'kompetensi', 'dibuat_pada',
            ]], 'links', 'meta'])
            ->assertJsonStructure(['data' => [2 => ['kompetensi' => [['id_kompetensi', 'nama_kompetensi', 'kategori']]]]]);
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('HR Client hanya melihat job milik kliennya, termasuk di ringkasan', function () {
        $hr = loginSebagai(Role::HrClient);
        JobRequirement::factory()->create(['id_klien' => $hr->idKlien(), 'posisi' => 'Milik Sendiri']);
        JobRequirement::factory()->count(3)->create();

        $this->getJson('/api/v1/job-requirements?id_klien='.Klien::factory()->create()->id_klien)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.posisi', 'Milik Sendiri')
            ->assertJsonPath('meta.ringkasan.total', 1);
    });

    it('bisa difilter status, indikator SLA, dan dicari posisi/klien tanpa mengubah ringkasan', function () {
        loginSebagai(Role::AccountManager);
        $klien = Klien::factory()->create(['nama_perusahaan' => 'PT Cari Sini']);
        JobRequirement::factory()->create(['id_klien' => $klien->id_klien, 'posisi' => 'QA Engineer', 'status_req' => StatusReq::Diajukan]);
        JobRequirement::factory()->create(['posisi' => 'Data Analyst', 'status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->addDays(2)]);
        JobRequirement::factory()->create(['posisi' => 'Mobile Developer', 'status_req' => StatusReq::Draft, 'sla_deadline' => now()->subDay()]);

        $this->getJson('/api/v1/job-requirements?search=cari sini')->assertJsonCount(1, 'data')->assertJsonPath('data.0.posisi', 'QA Engineer');
        $this->getJson('/api/v1/job-requirements?status=draft')->assertJsonCount(1, 'data')->assertJsonPath('data.0.posisi', 'Mobile Developer');
        $this->getJson('/api/v1/job-requirements?indikator_sla=mendekati')->assertJsonCount(1, 'data')->assertJsonPath('data.0.indikator_sla', 'mendekati');
        $this->getJson('/api/v1/job-requirements?indikator_sla=terlambat')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.posisi', 'Mobile Developer')
            ->assertJsonPath('meta.ringkasan.total', 3);
    });

    it('menolak filter tidak valid (422)', function () {
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/job-requirements?status=selesai&indikator_sla=merah&per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'indikator_sla', 'per_page']);
    });

    it('menolak talent (403)', function () {
        loginSebagai(Role::Talent);

        $this->getJson('/api/v1/job-requirements')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    });
});

describe('POST /job-requirements', function () {
    it('HR Client membuat job untuk kliennya sendiri, id_klien yang dikirim diabaikan', function () {
        $hr = loginSebagai(Role::HrClient);
        $data = dataJobBaru(['id_klien' => Klien::factory()->create()->id_klien]);

        $this->postJson('/api/v1/job-requirements', $data)
            ->assertCreated()
            ->assertJsonPath('message', 'Job requirement berhasil dibuat.')
            ->assertJsonPath('data.klien.id_klien', $hr->idKlien())
            ->assertJsonPath('data.account_manager', null)
            ->assertJsonPath('data.status_req', 'draft')
            ->assertJsonCount(2, 'data.kompetensi');

        // R8: draft belum dicatat di log progres.
        expect(JobRequirement::sole()->progres)->toHaveCount(0);
    });

    it('AM memilih klien, menjadi AM penanggung jawab, dan job diajukan langsung dicatat (R8)', function () {
        $am = loginSebagai(Role::AccountManager);
        $klien = Klien::factory()->create();

        $this->postJson('/api/v1/job-requirements', dataJobBaru(['id_klien' => $klien->id_klien, 'status_req' => 'diajukan']))
            ->assertCreated()
            ->assertJsonPath('data.klien.id_klien', $klien->id_klien)
            ->assertJsonPath('data.account_manager.id_pegawai', $am->pegawai->id_pegawai)
            ->assertJsonPath('data.status_req', 'diajukan');

        $log = JobRequirement::sole()->progres;
        expect($log)->toHaveCount(1)
            ->and($log[0]->status_tahapan)->toBe(StatusTahapan::KebutuhanDibuat)
            ->and($log[0]->id_penanggung_jawab)->toBe($am->pegawai->id_pegawai);
    });

    it('menolak data tidak valid (422)', function (Role $role, array $data, array $kolom) {
        loginSebagai($role);

        $this->postJson('/api/v1/job-requirements', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($kolom);
    })->with([
        'kosong' => [Role::HrClient, [], ['posisi', 'jumlah_kebutuhan', 'sla_deadline', 'kompetensi']],
        'AM tanpa klien' => [Role::AccountManager, fn () => dataJobBaru(), ['id_klien']],
        'tanpa kompetensi (R7)' => [Role::HrClient, fn () => dataJobBaru(['kompetensi' => []]), ['kompetensi']],
        'deadline lewat' => [Role::HrClient, fn () => dataJobBaru(['sla_deadline' => now()->subDay()->toDateTimeString()]), ['sla_deadline']],
        'status selain draft/diajukan' => [Role::HrClient, fn () => dataJobBaru(['status_req' => 'terpenuhi']), ['status_req']],
        'jumlah 0 & kompetensi tidak ada' => [Role::HrClient, fn () => dataJobBaru(['jumlah_kebutuhan' => 0, 'kompetensi' => [999]]), ['jumlah_kebutuhan', 'kompetensi.0']],
    ]);

    it('memakai pesan Bahasa Indonesia untuk deadline dan kompetensi', function () {
        loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', dataJobBaru(['sla_deadline' => now()->subDay()->toDateTimeString(), 'kompetensi' => []]))
            ->assertJsonPath('errors.sla_deadline.0', 'SLA deadline harus setelah waktu sekarang.')
            ->assertJsonPath('errors.kompetensi.0', 'Pilih minimal 1 kompetensi.');
    });

    it('menolak role selain HR Client dan AM (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/job-requirements', dataJobBaru())
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(JobRequirement::count())->toBe(0);
    })->with(roleSelain(Role::HrClient, Role::AccountManager));
});

describe('GET /job-requirements/{id}', function () {
    it('menampilkan detail beserta ringkasan progres', function () {
        loginSebagai(Role::TalentSupport);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses, 'jumlah_kebutuhan' => 3]);
        ScreeningTalent::factory()->for($job)->create();
        ScreeningTalent::factory()->for($job)->lolos()->create();
        ScreeningTalent::factory()->for($job)->create(['hasil_screening' => HasilScreening::TidakLolos]);

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}")
            ->assertOk()
            ->assertJsonPath('data.ringkasan_progres', [
                'jumlah_screening' => 3,
                'screening_pending' => 1,
                'screening_lolos' => 1,
                'screening_tidak_lolos' => 1,
                'terpenuhi' => 0,
                'sisa_kebutuhan' => 3,
            ])
            ->assertJsonPath('data.progres_terakhir.status_tahapan', 'screening');
    });

    it('HR Client boleh melihat job kliennya tapi ditolak untuk klien lain (403)', function () {
        $hr = loginSebagai(Role::HrClient);
        $milik = JobRequirement::factory()->create(['id_klien' => $hr->idKlien()]);
        $lain = JobRequirement::factory()->create();

        $this->getJson("/api/v1/job-requirements/{$milik->id_job_req}")->assertOk();
        $this->getJson("/api/v1/job-requirements/{$lain->id_job_req}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke job requirement ini.']);
    });

    it('membalas 404 jika data tidak ada', function () {
        loginSebagai(Role::Manager);

        $this->getJson('/api/v1/job-requirements/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });
});

describe('PUT /job-requirements/{id}', function () {
    it('mengubah sebagian isi dan mengganti kompetensi saat draft/diajukan', function (StatusReq $status) {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => $status, 'posisi' => 'Lama']);
        $job->kompetensi()->attach(Kompetensi::factory()->count(3)->create());
        $baru = Kompetensi::factory()->create();

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => 'Baru', 'kompetensi' => [$baru->id_kompetensi]])
            ->assertOk()
            ->assertJsonPath('message', 'Job requirement berhasil diperbarui.')
            ->assertJsonPath('data.posisi', 'Baru')
            ->assertJsonPath('data.jumlah_kebutuhan', $job->jumlah_kebutuhan)
            ->assertJsonPath('data.kompetensi.0.id_kompetensi', $baru->id_kompetensi)
            ->assertJsonCount(1, 'data.kompetensi');
    })->with([StatusReq::Draft, StatusReq::Diajukan]);

    it('menolak perubahan saat job sudah diproses/terpenuhi/dibatalkan (422)', function (StatusReq $status) {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => $status]);

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => 'Baru'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_req.0', 'Job requirement hanya dapat diubah saat berstatus draft atau diajukan.');
    })->with([StatusReq::Diproses, StatusReq::Terpenuhi, StatusReq::Dibatalkan]);

    it('menolak data tidak valid (422)', function () {
        $hr = loginSebagai(Role::HrClient);
        $job = JobRequirement::factory()->create(['id_klien' => $hr->idKlien()]);

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => '', 'kompetensi' => [], 'pengalaman_min' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['posisi', 'kompetensi', 'pengalaman_min']);
    });

    it('menolak HR Client dari klien lain (403)', function () {
        loginSebagai(Role::HrClient);
        $job = JobRequirement::factory()->create();

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => 'Baru'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke job requirement ini.']);
    });

    it('menolak role selain HR Client dan AM (403)', function (Role $role) {
        loginSebagai($role);
        $job = JobRequirement::factory()->create();

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['posisi' => 'Baru'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::HrClient, Role::AccountManager));
});

describe('PATCH /job-requirements/{id}/status', function () {
    it('mengajukan job draft dan mencatat progres kebutuhan_dibuat (R8)', function () {
        $hr = loginSebagai(Role::HrClient);
        $job = JobRequirement::factory()->create(['id_klien' => $hr->idKlien(), 'id_am' => null]);

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])
            ->assertOk()
            ->assertJsonPath('message', 'Job requirement berhasil diajukan.')
            ->assertJsonPath('data.status_req', 'diajukan');

        expect($job->progres()->sole())
            ->status_tahapan->toBe(StatusTahapan::KebutuhanDibuat)
            ->indikator_sla->value->toBe('aman');
    });

    it('menolak pengajuan selain dari draft atau saat deadline sudah lewat (422)', function (array $atribut, string $pesan) {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create($atribut);

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_req.0', $pesan);
    })->with([
        'sudah diajukan' => [['status_req' => StatusReq::Diajukan], 'Hanya job requirement berstatus draft yang dapat diajukan.'],
        'deadline lewat' => [['sla_deadline' => now()->subHour()], 'SLA deadline sudah lewat. Perbarui SLA deadline sebelum mengajukan.'],
    ]);

    it('membatalkan job dan mengembalikan talent yang sedang diproses menjadi tersedia', function () {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses]);
        $diproses = Talent::factory()->lengkap()->create(['status_kandidat' => StatusKandidat::DalamProses]);
        ScreeningTalent::factory()->for($job)->for($diproses)->lolos()->create();
        // Talent yang diproses untuk job lain tidak ikut dilepas.
        $talentLain = Talent::factory()->lengkap()->create(['status_kandidat' => StatusKandidat::DalamProses]);
        ScreeningTalent::factory()->for($talentLain)->create();

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'dibatalkan'])
            ->assertOk()
            ->assertJsonPath('message', 'Job requirement berhasil dibatalkan.')
            ->assertJsonPath('data.status_req', 'dibatalkan');

        expect($diproses->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($talentLain->fresh()->status_kandidat)->toBe(StatusKandidat::DalamProses);
    });

    it('menolak pembatalan job terpenuhi/dibatalkan dan status tidak dikenal (422)', function () {
        loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Terpenuhi]);

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'dibatalkan'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_req.0', 'Job requirement yang sudah terpenuhi atau dibatalkan tidak dapat dibatalkan.');

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'terpenuhi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_req']);
    });

    it('menolak HR Client dari klien lain dan role lain (403)', function () {
        $job = JobRequirement::factory()->create();

        $this->actingAs(HrClient::factory()->create()->user)
            ->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke job requirement ini.']);

        $this->actingAs(buatUser(Role::TalentSupport))
            ->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])
            ->assertForbidden();

        expect($job->fresh()->status_req)->toBe(StatusReq::Draft);
    });
});
