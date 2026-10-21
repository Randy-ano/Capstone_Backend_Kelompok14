<?php

use App\Enums\IndikatorSla;
use App\Enums\Role;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\ProgresPemenuhan;
use App\Models\ScreeningTalent;
use App\Services\ProgresService;

describe('Indikator SLA (R13)', function () {
    it('menghitung indikator dari deadline dan status, sama di PHP dan filter SQL', function (string $selisih, StatusReq $status, IndikatorSla $harapan) {
        $job = JobRequirement::factory()->create(['sla_deadline' => now()->modify($selisih), 'status_req' => $status]);
        $service = app(ProgresService::class);

        expect($service->indikatorSla($job))->toBe($harapan);

        foreach (IndikatorSla::cases() as $indikator) {
            $cocok = $service->filterIndikatorSla(JobRequirement::query(), $indikator)->exists();
            expect($cocok)->toBe($indikator === $harapan, "Filter SQL {$indikator->value} tidak sama dengan PHP");
        }
    })->with([
        'lewat deadline' => ['-1 hour', StatusReq::Diproses, IndikatorSla::Terlambat],
        'sisa 2 hari' => ['+2 days', StatusReq::Diajukan, IndikatorSla::Mendekati],
        'sisa tepat 3 hari kurang 1 menit' => ['+3 days -1 minute', StatusReq::Diajukan, IndikatorSla::Mendekati],
        'sisa 4 hari' => ['+4 days', StatusReq::Diajukan, IndikatorSla::Aman],
        'terpenuhi walau lewat' => ['-5 days', StatusReq::Terpenuhi, IndikatorSla::Aman],
        'dibatalkan walau lewat' => ['-5 days', StatusReq::Dibatalkan, IndikatorSla::Aman],
    ]);

    it('menyimpan indikator SLA saat log dibuat', function () {
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft, 'sla_deadline' => now()->addDay()]);

        $job->update(['status_req' => StatusReq::Diajukan]);

        expect($job->progres()->sole()->indikator_sla)->toBe(IndikatorSla::Mendekati);
    });
});

describe('GET /job-requirements/{id}/progres', function () {
    it('menampilkan indikator SLA terkini dan semua log terbaru dulu', function () {
        loginSebagai(Role::Manager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft]);
        $job->update(['status_req' => StatusReq::Diajukan]);
        $screening = ScreeningTalent::factory()->for($job)->create();

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/progres")
            ->assertOk()
            ->assertJsonPath('data.id_job_req', $job->id_job_req)
            ->assertJsonPath('data.indikator_sla', 'aman')
            ->assertJsonCount(2, 'data.log_progres')
            ->assertJsonPath('data.log_progres.0.status_tahapan', 'screening')
            ->assertJsonPath('data.log_progres.0.talent.id_talent', $screening->id_talent)
            ->assertJsonPath('data.log_progres.0.penanggung_jawab.id_pegawai', $screening->id_ts)
            ->assertJsonPath('data.log_progres.1.status_tahapan', 'kebutuhan_dibuat')
            ->assertJsonPath('data.log_progres.1.talent', null)
            ->assertJsonStructure(['data' => ['log_progres' => [['id_progres', 'status_tahapan', 'indikator_sla', 'log_waktu_pembaruan', 'talent', 'penanggung_jawab']]]]);
    });

    it('HR Client melihat progres job kliennya, ditolak untuk klien lain (403)', function () {
        $hr = loginSebagai(Role::HrClient);
        $milik = JobRequirement::factory()->create(['id_klien' => $hr->idKlien()]);
        $lain = JobRequirement::factory()->create();

        $this->getJson("/api/v1/job-requirements/{$milik->id_job_req}/progres")->assertOk();
        $this->getJson("/api/v1/job-requirements/{$lain->id_job_req}/progres")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke job requirement ini.']);
    });

    it('menolak talent (403)', function () {
        loginSebagai(Role::Talent);
        $job = JobRequirement::factory()->create();

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/progres")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    });
});

describe('GET /progres', function () {
    it('menampilkan semua job dengan SLA terkini, log terakhir, urut deadline terdekat', function (Role $role) {
        loginSebagai($role);
        $nanti = JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->addDays(20)]);
        $lewat = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses, 'sla_deadline' => now()->subDay()]);
        ProgresPemenuhan::factory()->for($lewat)->create(['status_tahapan' => StatusTahapan::KebutuhanDibuat]);
        ScreeningTalent::factory()->for($lewat)->create();

        $this->getJson('/api/v1/progres')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id_job_req', $lewat->id_job_req)
            ->assertJsonPath('data.0.indikator_sla', 'terlambat')
            ->assertJsonPath('data.0.jumlah_screening', 1)
            ->assertJsonPath('data.0.progres_terakhir.status_tahapan', 'screening')
            ->assertJsonPath('data.1.id_job_req', $nanti->id_job_req)
            ->assertJsonPath('data.1.progres_terakhir.status_tahapan', 'kebutuhan_dibuat');
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('bisa difilter indikator SLA, status, dan klien', function () {
        loginSebagai(Role::Manager);
        $klien = Klien::factory()->create();
        JobRequirement::factory()->create(['id_klien' => $klien->id_klien, 'status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->subDay()]);
        JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->addDays(10)]);

        $this->getJson('/api/v1/progres?indikator_sla=terlambat')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/progres?indikator_sla=aman&status=diajukan')->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/progres?id_klien={$klien->id_klien}")->assertJsonCount(1, 'data');
    });

    it('menolak talent dan HR Client (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/progres')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with([Role::Talent, Role::HrClient]);
});

describe('GET /klien', function () {
    it('menampilkan daftar klien untuk internal, bisa dicari', function (Role $role) {
        loginSebagai($role);
        Klien::factory()->create(['nama_perusahaan' => 'PT Cari Saya']);
        Klien::factory()->count(2)->create();

        $this->getJson('/api/v1/klien?search=cari saya')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_perusahaan', 'PT Cari Saya');
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('menolak talent dan HR Client (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/klien')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with([Role::Talent, Role::HrClient]);

    it('menolak per_page tidak valid (422)', function () {
        loginSebagai(Role::AccountManager);

        $this->getJson('/api/v1/klien?per_page=0')->assertUnprocessable()->assertJsonValidationErrors(['per_page']);
    });
});
