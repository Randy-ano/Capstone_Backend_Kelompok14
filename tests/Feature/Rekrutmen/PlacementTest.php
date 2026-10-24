<?php

use App\Enums\Role;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\Kompetensi;
use App\Models\Placement;
use App\Models\Talent;
use App\Models\TalentPool;

/** Evaluasi diterima untuk job tertentu, siap dibuatkan placement. */
function evaluasiDiterima(?App\Models\JobRequirement $job = null): App\Models\EvaluasiKandidat
{
    return rekrutmenEvaluasi(rekrutmenPengajuan($job ?? rekrutmenJob(), StatusPengajuan::LanjutInterview));
}

describe('POST /placement', function () {
    it('membuat placement dari evaluasi diterima dan menjalankan R19 + log progres', function () {
        loginSebagai(Role::TalentSupport);
        $job = rekrutmenJob(atribut: ['jumlah_kebutuhan' => 2, 'posisi' => 'Backend Developer']);
        $evaluasi = evaluasiDiterima($job);
        $talent = $evaluasi->pengajuan->talent;

        $this->postJson('/api/v1/placement', ['id_evaluasi' => $evaluasi->id_evaluasi, 'tanggal_mulai' => '2026-11-01'])
            ->assertCreated()
            ->assertJsonPath('message', 'Placement berhasil dibuat.')
            ->assertJsonPath('data.posisi_penempatan', 'Backend Developer')
            ->assertJsonPath('data.status_placement', 'aktif')
            ->assertJsonPath('data.talent.id_talent', $talent->id_talent)
            ->assertJsonPath('data.klien.id_klien', $job->id_klien)
            ->assertJsonPath('data.ringkasan.jumlah_cuti', 0);

        expect($talent->fresh()->status_kandidat)->toBe(StatusKandidat::Ditempatkan)
            ->and($talent->talentPool->fresh()->status_ketersediaan)->toBe(StatusKetersediaan::TidakTersedia)
            ->and($job->fresh()->status_req)->toBe(StatusReq::Diproses) // baru 1 dari 2
            ->and($job->progres()->latest('id_progres')->first()->status_tahapan)->toBe(StatusTahapan::Placement);
    });

    it('job menjadi terpenuhi saat kebutuhan tercapai dan kandidat lain yang masih diproses dilepas', function () {
        loginSebagai(Role::TalentSupport);
        $job = rekrutmenJob(atribut: ['jumlah_kebutuhan' => 1]);
        $evaluasi = evaluasiDiterima($job);
        $lain = rekrutmenPengajuan($job)->talent; // kandidat lain, masih dalam proses

        $this->postJson('/api/v1/placement', ['id_evaluasi' => $evaluasi->id_evaluasi, 'tanggal_mulai' => '2026-11-01', 'posisi_penempatan' => 'Lead Backend'])
            ->assertCreated()
            ->assertJsonPath('data.posisi_penempatan', 'Lead Backend');

        expect($job->fresh()->status_req)->toBe(StatusReq::Terpenuhi)
            ->and($lain->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($evaluasi->pengajuan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::Ditempatkan);
    });

    it('menolak evaluasi ditolak, sudah punya placement, dan job sudah terpenuhi (422)', function (string $kasus, string $pesan) {
        loginSebagai(Role::TalentSupport);
        $evaluasi = match ($kasus) {
            'ditolak' => rekrutmenEvaluasi(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview), StatusEvaluasi::Ditolak),
            'sudah placement' => tap(evaluasiDiterima(), fn ($e) => Placement::factory()->create(['id_evaluasi' => $e->id_evaluasi])),
            'job terpenuhi' => tap(evaluasiDiterima(), fn ($e) => $e->pengajuan->jobRequirement->update(['status_req' => StatusReq::Terpenuhi])),
        };

        $this->postJson('/api/v1/placement', ['id_evaluasi' => $evaluasi->id_evaluasi, 'tanggal_mulai' => '2026-11-01'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_evaluasi.0', $pesan);
    })->with([
        ['ditolak', 'Placement hanya dapat dibuat dari evaluasi yang diterima.'],
        ['sudah placement', 'Evaluasi ini sudah memiliki placement.'],
        ['job terpenuhi', 'Job requirement berstatus terpenuhi; proses kandidat tidak dapat dilanjutkan.'],
    ]);

    it('menolak tanggal tidak valid (422)', function () {
        loginSebagai(Role::TalentSupport);
        $evaluasi = evaluasiDiterima();

        $this->postJson('/api/v1/placement', ['id_evaluasi' => $evaluasi->id_evaluasi, 'tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-10-01'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_selesai.0', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
    });

    it('menolak selain Talent Support (403)', function (Role $role) {
        loginSebagai($role);
        $evaluasi = evaluasiDiterima();

        $this->postJson('/api/v1/placement', ['id_evaluasi' => $evaluasi->id_evaluasi, 'tanggal_mulai' => '2026-11-01'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(Placement::count())->toBe(0);
    })->with(roleSelain(Role::TalentSupport));
});

describe('GET /placement', function () {
    it('membatasi data sesuai role', function () {
        $semua = Placement::factory()->count(2)->create();
        $hrUser = App\Models\HrClient::factory()->create(['id_klien' => $semua[0]->id_klien])->user;
        $talentUser = $semua[1]->talent->user;

        $this->actingAs(buatUser(Role::Manager))->getJson('/api/v1/placement')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($hrUser)->getJson('/api/v1/placement')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_placement', $semua[0]->id_placement);
        $this->actingAs($talentUser)->getJson('/api/v1/placement')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_placement', $semua[1]->id_placement);
    });

    it('bisa difilter status dan dicari', function () {
        loginSebagai(Role::TalentSupport);
        $p = Placement::factory()->create(['posisi_penempatan' => 'QA Lead']);
        Placement::factory()->create(['status_placement' => StatusPlacement::Selesai]);

        $this->getJson('/api/v1/placement?status=aktif')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/placement?search=qa lead')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_placement', $p->id_placement);
        $this->getJson('/api/v1/placement?status=arsip')->assertUnprocessable()->assertJsonValidationErrors(['status']);
    });
});

describe('GET /placement/{id}', function () {
    it('menampilkan detail dengan ringkasan; menolak klien lain dan talent lain (403)', function () {
        $p = Placement::factory()->create();

        $this->actingAs($p->talent->user)->getJson("/api/v1/placement/{$p->id_placement}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['ringkasan' => ['jumlah_cuti', 'jumlah_shift', 'jumlah_monitoring', 'jumlah_laporan_keluhan']]]);

        foreach ([Role::HrClient, Role::Talent] as $role) {
            $this->actingAs(buatUser($role))->getJson("/api/v1/placement/{$p->id_placement}")
                ->assertForbidden()
                ->assertExactJson(['message' => 'Anda tidak memiliki akses ke placement ini.']);
        }
    });
});

describe('PATCH /placement/{id}/status', function () {
    it('mengakhiri placement mengembalikan talent ke pool (R20)', function (Role $role, string $status) {
        loginSebagai($role);
        $p = Placement::factory()->create(['tanggal_mulai' => '2026-01-01']);
        $p->talent->update(['status_kandidat' => StatusKandidat::Ditempatkan]);
        $pool = TalentPool::factory()->for($p->talent)->create(['status_ketersediaan' => StatusKetersediaan::TidakTersedia]);

        $this->patchJson("/api/v1/placement/{$p->id_placement}/status", ['status_placement' => $status, 'tanggal_selesai' => '2026-06-30'])
            ->assertOk()
            ->assertJsonPath('data.status_placement', $status)
            ->assertJsonPath('data.tanggal_selesai', '2026-06-30');

        expect($p->talent->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($pool->fresh()->status_ketersediaan)->toBe(StatusKetersediaan::Tersedia);
    })->with([[Role::TalentSupport, 'selesai'], [Role::Manager, 'dihentikan']]);

    it('tanggal selesai default hari ini jika kosong', function () {
        loginSebagai(Role::Manager);
        $p = Placement::factory()->create(['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => null]);

        $this->patchJson("/api/v1/placement/{$p->id_placement}/status", ['status_placement' => 'dihentikan'])
            ->assertOk()
            ->assertJsonPath('message', 'Placement dihentikan.')
            ->assertJsonPath('data.tanggal_selesai', now()->toDateString());
    });

    it('menolak placement yang sudah berakhir, tanggal sebelum mulai, dan status tidak dikenal (422)', function () {
        loginSebagai(Role::TalentSupport);
        $selesai = Placement::factory()->create(['status_placement' => StatusPlacement::Selesai]);
        $aktif = Placement::factory()->create(['tanggal_mulai' => '2026-05-01']);

        $this->patchJson("/api/v1/placement/{$selesai->id_placement}/status", ['status_placement' => 'dihentikan'])
            ->assertUnprocessable()->assertJsonPath('errors.status_placement.0', 'Hanya placement aktif yang dapat diakhiri.');
        $this->patchJson("/api/v1/placement/{$aktif->id_placement}/status", ['status_placement' => 'selesai', 'tanggal_selesai' => '2026-04-01'])
            ->assertUnprocessable()->assertJsonPath('errors.tanggal_selesai.0', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        $this->patchJson("/api/v1/placement/{$aktif->id_placement}/status", ['status_placement' => 'aktif'])
            ->assertUnprocessable()->assertJsonValidationErrors(['status_placement']);
    });

    it('menolak selain TS dan Manager (403)', function (Role $role) {
        loginSebagai($role);
        $p = Placement::factory()->create();

        $this->patchJson("/api/v1/placement/{$p->id_placement}/status", ['status_placement' => 'selesai'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::TalentSupport, Role::Manager));
});

describe('Alur penuh rekrutmen (Fase 5)', function () {
    it('job → screening → pengajuan → interview → evaluasi → placement → job terpenuhi', function () {
        $hrUser = buatUser(Role::HrClient);
        $am = buatUser(Role::AccountManager);
        $ts = buatUser(Role::TalentSupport);
        $laravel = Kompetensi::factory()->create();
        $talent = Talent::factory()->lengkap()->create();
        $talent->kompetensi()->attach($laravel);
        TalentPool::factory()->for($talent)->create();

        // FR-05: HR Client membuat & mengajukan job.
        $idJob = $this->actingAs($hrUser)->postJson('/api/v1/job-requirements', [
            'posisi' => 'Backend Developer', 'jumlah_kebutuhan' => 1, 'pengalaman_min' => 0,
            'sla_deadline' => now()->addDays(20)->toDateTimeString(), 'kompetensi' => [$laravel->id_kompetensi], 'status_req' => 'diajukan',
        ])->assertCreated()->json('data.id_job_req');

        // FR-07/08: TS melihat rekomendasi lalu screening, kemudian lolos.
        $this->actingAs($ts)->getJson("/api/v1/job-requirements/{$idJob}/rekomendasi")->assertJsonPath('data.0.id_talent', $talent->id_talent);
        $idScreening = $this->postJson('/api/v1/screening', ['id_job_req' => $idJob, 'id_talent' => $talent->id_talent])->assertCreated()->json('data.id_screening');
        $this->putJson("/api/v1/screening/{$idScreening}", ['hasil_screening' => 'lolos'])->assertOk();

        // FR-10: AM mengajukan; HR Client melanjutkan ke interview.
        $idPengajuan = $this->actingAs($am)->postJson('/api/v1/pengajuan', ['id_screening' => $idScreening])->assertCreated()->json('data.id_pengajuan');
        $this->actingAs($hrUser)->patchJson("/api/v1/pengajuan/{$idPengajuan}/status", ['status_pengajuan' => 'lanjut_interview'])->assertOk();

        // FR-11: AM menjadwalkan interview hari ini, lalu menandainya selesai.
        $idInterview = $this->actingAs($am)->postJson('/api/v1/interview', [
            'id_pengajuan' => $idPengajuan, 'tanggal_interview' => now()->toDateString(), 'waktu_interview' => '09:00',
        ])->assertCreated()->json('data.id_interview');
        $this->actingAs($am)->putJson("/api/v1/interview/{$idInterview}", ['status_interview' => 'selesai'])->assertOk();

        // FR-12: HR Client menerima kandidat.
        $idEvaluasi = $this->actingAs($hrUser)->postJson('/api/v1/evaluasi', ['id_pengajuan' => $idPengajuan, 'nilai_score' => 90, 'status_evaluasi' => 'diterima'])->assertCreated()->json('data.id_evaluasi');

        // FR-17: TS membuat placement → job terpenuhi.
        $this->actingAs($ts)->postJson('/api/v1/placement', ['id_evaluasi' => $idEvaluasi, 'tanggal_mulai' => now()->addWeek()->toDateString()])->assertCreated();

        $job = App\Models\JobRequirement::find($idJob);
        expect($job->status_req)->toBe(StatusReq::Terpenuhi)
            ->and($talent->fresh()->status_kandidat)->toBe(StatusKandidat::Ditempatkan)
            ->and($talent->talentPool->fresh()->status_ketersediaan)->toBe(StatusKetersediaan::TidakTersedia)
            ->and(statusInterview($idInterview))->toBe(StatusInterview::Selesai)
            ->and($job->progres()->orderBy('id_progres')->pluck('status_tahapan')->map->value->all())
            ->toBe(['kebutuhan_dibuat', 'screening', 'diajukan', 'interview', 'evaluasi', 'placement']);

        // Detail job (HR Client) menunjukkan kebutuhan terpenuhi, SLA aman.
        $this->actingAs($hrUser)->getJson("/api/v1/job-requirements/{$idJob}")
            ->assertJsonPath('data.terpenuhi', 1)
            ->assertJsonPath('data.status_req', 'terpenuhi')
            ->assertJsonPath('data.indikator_sla', 'aman');
    });
});

function statusInterview(int $id): StatusInterview
{
    return App\Models\JadwalInterview::find($id)->status_interview;
}
