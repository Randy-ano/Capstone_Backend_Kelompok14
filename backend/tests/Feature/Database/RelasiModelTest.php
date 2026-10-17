<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusReq;
use App\Models\Dokumen;
use App\Models\EvaluasiKandidat;
use App\Models\HrClient;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\LaporanKeluhan;
use App\Models\MonitoringTalent;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanKandidat;
use App\Models\Placement;
use App\Models\ProgresPemenuhan;
use App\Models\ScreeningTalent;
use App\Models\ShiftingTalent;
use App\Models\TandaTanganDokumen;
use App\Models\Talent;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken;

describe('akun', function () {
    it('menghubungkan user dengan profil pegawai, hr_client, dan talent', function () {
        $pegawai = Pegawai::factory()->talentSupport()->create();
        $hr = HrClient::factory()->create();
        $talent = Talent::factory()->create();

        expect($pegawai->user->role)->toBe(Role::TalentSupport)
            ->and($pegawai->user->pegawai->is($pegawai))->toBeTrue()
            ->and($hr->user->hrClient->is($hr))->toBeTrue()
            ->and($talent->user->talent->is($talent))->toBeTrue();
    });

    it('menghubungkan klien dengan hr_client', function () {
        $klien = Klien::factory()->has(HrClient::factory()->count(2), 'hrClients')->create();

        expect($klien->hrClients)->toHaveCount(2)
            ->and($klien->hrClients->first()->klien->is($klien))->toBeTrue();
    });

    it('menghubungkan user dengan notifikasinya', function () {
        $notifikasi = Notifikasi::factory()->create();

        expect($notifikasi->penerima->notifikasi->first()->is($notifikasi))->toBeTrue();
    });

    it('membuat token Sanctum untuk user ber-PK id_user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        expect(PersonalAccessToken::findToken($token)->tokenable->is($user))->toBeTrue();
    });

    it('menyembunyikan password dan memakai enum untuk role', function () {
        $user = User::factory()->role(Role::Manager)->create();

        expect($user->role)->toBe(Role::Manager)
            ->and($user->status_aktif)->toBeTrue()
            ->and($user->toArray())->not->toHaveKey('password');
    });
});

describe('kebutuhan & kandidat', function () {
    it('menghubungkan talent dan kompetensi (M:N)', function () {
        $talent = Talent::factory()->create();
        $kompetensi = Kompetensi::factory()->count(3)->create();
        $talent->kompetensi()->attach($kompetensi);

        expect($talent->kompetensi)->toHaveCount(3)
            ->and($kompetensi->first()->talents->first()->is($talent))->toBeTrue();
    });

    it('menghubungkan job requirement dengan klien, AM, dan kompetensi', function () {
        $job = JobRequirement::factory()->create();
        $kompetensi = Kompetensi::factory()->create();
        $job->kompetensi()->attach($kompetensi);

        expect($job->klien->jobRequirements->first()->is($job))->toBeTrue()
            ->and($job->accountManager->jobRequirements->first()->is($job))->toBeTrue()
            ->and($kompetensi->jobRequirements->first()->is($job))->toBeTrue();
    });

    it('menghubungkan talent dengan talent pool', function () {
        $pool = TalentPool::factory()->create();

        expect($pool->talent->talentPool->is($pool))->toBeTrue()
            ->and($pool->talent->status_kelengkapan)->toBe(StatusKelengkapan::Lengkap)
            ->and($pool->talent->kompetensi)->not->toBeEmpty();
    });

    it('mengisi nilai default status sesuai spec', function () {
        $talent = Talent::create(['id_user' => User::factory()->create()->id_user, 'nama_lengkap' => 'Uji']);
        $job = JobRequirement::create([
            'id_klien' => Klien::factory()->create()->id_klien,
            'posisi' => 'Backend Developer',
            'jumlah_kebutuhan' => 1,
            'sla_deadline' => now()->addDays(30),
        ]);

        expect($talent->status_kelengkapan)->toBe(StatusKelengkapan::BelumLengkap)
            ->and($talent->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($job->status_req)->toBe(StatusReq::Draft)
            ->and($job->pengalaman_min)->toBe(0);
    });
});

describe('proses rekrutmen', function () {
    it('menghubungkan screening dengan talent, job, dan talent support', function () {
        $screening = ScreeningTalent::factory()->create();

        expect($screening->talent->screenings->first()->is($screening))->toBeTrue()
            ->and($screening->jobRequirement->screenings->first()->is($screening))->toBeTrue()
            ->and($screening->talentSupport->screenings->first()->is($screening))->toBeTrue();
    });

    it('menghubungkan progres dengan job, talent, dan penanggung jawab', function () {
        $talent = Talent::factory()->create();
        $pj = Pegawai::factory()->create();
        $progres = ProgresPemenuhan::factory()->create(['id_talent' => $talent->id_talent, 'id_penanggung_jawab' => $pj->id_pegawai]);

        expect($progres->jobRequirement->progres->first()->is($progres))->toBeTrue()
            ->and($talent->progres->first()->is($progres))->toBeTrue()
            ->and($pj->progresDitangani->first()->is($progres))->toBeTrue();
    });

    it('menyalin talent & job dari screening ke pengajuan', function () {
        $pengajuan = PengajuanKandidat::factory()->create();
        $screening = $pengajuan->screening;

        expect($pengajuan->id_talent)->toBe($screening->id_talent)
            ->and($pengajuan->id_job_req)->toBe($screening->id_job_req)
            ->and($screening->pengajuan->is($pengajuan))->toBeTrue()
            ->and($pengajuan->talent->pengajuanKandidat->first()->is($pengajuan))->toBeTrue()
            ->and($pengajuan->jobRequirement->pengajuanKandidat->first()->is($pengajuan))->toBeTrue()
            ->and($pengajuan->accountManager->pengajuanKandidat->first()->is($pengajuan))->toBeTrue();
    });

    it('menghubungkan pengajuan dengan beberapa ronde interview dan satu evaluasi', function () {
        $pengajuan = PengajuanKandidat::factory()->lanjutInterview()->create();
        JadwalInterview::factory()->count(2)->create(['id_pengajuan' => $pengajuan->id_pengajuan]);
        $evaluasi = EvaluasiKandidat::factory()->create(['id_pengajuan' => $pengajuan->id_pengajuan]);

        expect($pengajuan->interviews)->toHaveCount(2)
            ->and($pengajuan->interviews->first()->pengajuan->is($pengajuan))->toBeTrue()
            ->and($pengajuan->evaluasi->is($evaluasi))->toBeTrue()
            ->and($evaluasi->hrClient->evaluasi->first()->is($evaluasi))->toBeTrue()
            ->and($evaluasi->hrClient->id_klien)->toBe($pengajuan->jobRequirement->id_klien);
    });
});

describe('penempatan & operasional', function () {
    it('menyalin talent, job, dan klien dari evaluasi ke placement', function () {
        $placement = Placement::factory()->create();
        $pengajuan = $placement->evaluasi->pengajuan;

        expect($placement->id_talent)->toBe($pengajuan->id_talent)
            ->and($placement->id_job_req)->toBe($pengajuan->id_job_req)
            ->and($placement->id_klien)->toBe($pengajuan->jobRequirement->id_klien)
            ->and($placement->evaluasi->placement->is($placement))->toBeTrue()
            ->and($placement->talent->placements->first()->is($placement))->toBeTrue()
            ->and($placement->jobRequirement->placements->first()->is($placement))->toBeTrue()
            ->and($placement->klien->placements->first()->is($placement))->toBeTrue();
    });

    it('menghubungkan cuti dengan talent, placement, dan kedua approver', function () {
        $hr = HrClient::factory()->create();
        $manager = Pegawai::factory()->manager()->create();
        $cuti = PengajuanCuti::factory()->create([
            'id_approver_klien' => $hr->id_hr_client,
            'id_approver_manager' => $manager->id_pegawai,
        ]);

        expect($cuti->id_talent)->toBe($cuti->placement->id_talent)
            ->and($cuti->talent->cuti->first()->is($cuti))->toBeTrue()
            ->and($cuti->placement->cuti->first()->is($cuti))->toBeTrue()
            ->and($hr->cutiDiputuskan->first()->is($cuti))->toBeTrue()
            ->and($manager->cutiDiputuskan->first()->is($cuti))->toBeTrue();
    });

    it('menghubungkan shift dengan placement dan pengelola', function () {
        $pengelola = User::factory()->role(Role::Manager)->create();
        $shift = ShiftingTalent::factory()->create(['id_pengelola' => $pengelola->id_user]);

        expect($shift->placement->shifts->first()->is($shift))->toBeTrue()
            ->and($pengelola->shiftDikelola->first()->is($shift))->toBeTrue();
    });

    it('menghubungkan monitoring dengan placement dan manager', function () {
        $monitoring = MonitoringTalent::factory()->create();

        expect($monitoring->placement->monitorings->first()->is($monitoring))->toBeTrue()
            ->and($monitoring->manager->monitorings->first()->is($monitoring))->toBeTrue();
    });

    it('menghubungkan laporan keluhan dengan placement, pelapor, dan penindak lanjut', function () {
        $am = Pegawai::factory()->create();
        $laporan = LaporanKeluhan::factory()->create(['id_penindak_lanjut' => $am->id_pegawai]);

        expect($laporan->placement->laporanKeluhan->first()->is($laporan))->toBeTrue()
            ->and($laporan->hrClient->laporanKeluhan->first()->is($laporan))->toBeTrue()
            ->and($laporan->hrClient->id_klien)->toBe($laporan->placement->id_klien)
            ->and($am->laporanDitindaklanjuti->first()->is($laporan))->toBeTrue();
    });
});

describe('dokumen', function () {
    it('menghubungkan dokumen dengan pengunggah, klien, talent, placement, dan tanda tangan', function () {
        $placement = Placement::factory()->create();
        $dokumen = Dokumen::factory()->create([
            'id_klien' => $placement->id_klien,
            'id_talent' => $placement->id_talent,
            'id_placement' => $placement->id_placement,
            'jenis_dokumen' => JenisDokumen::KontrakKerja,
        ]);
        $ttd = TandaTanganDokumen::factory()->create(['id_dokumen' => $dokumen->id_dokumen]);

        expect($dokumen->jenis_dokumen)->toBe(JenisDokumen::KontrakKerja)
            ->and($dokumen->uploader->dokumenDiunggah->first()->is($dokumen))->toBeTrue()
            ->and($dokumen->klien->dokumen->first()->is($dokumen))->toBeTrue()
            ->and($dokumen->talent->dokumen->first()->is($dokumen))->toBeTrue()
            ->and($dokumen->placement->dokumen->first()->is($dokumen))->toBeTrue()
            ->and($dokumen->tandaTangan->first()->is($ttd))->toBeTrue()
            ->and($ttd->penandatangan->tandaTangan->first()->is($ttd))->toBeTrue();
    });
});

describe('constraint database', function () {
    it('menghapus profil talent saat user dihapus (cascade)', function () {
        $talent = Talent::factory()->create();
        $talent->user->delete();

        expect(Talent::find($talent->id_talent))->toBeNull();
    });

    it('menolak hapus klien yang masih punya HR (restrict)', function () {
        $hr = HrClient::factory()->create();

        expect(fn () => $hr->klien->delete())->toThrow(QueryException::class);
    });

    it('mengosongkan id_am saat pegawai AM dihapus (set null)', function () {
        $job = JobRequirement::factory()->create();
        $job->accountManager->delete();

        expect($job->refresh()->id_am)->toBeNull();
    });

    it('menolak screening ganda untuk talent & job yang sama', function () {
        $screening = ScreeningTalent::factory()->create();

        expect(fn () => ScreeningTalent::factory()->create([
            'id_talent' => $screening->id_talent,
            'id_job_req' => $screening->id_job_req,
        ]))->toThrow(QueryException::class);
    });

    it('menolak shift ganda untuk placement & tanggal yang sama', function () {
        $shift = ShiftingTalent::factory()->create();

        expect(fn () => ShiftingTalent::factory()->create([
            'id_placement' => $shift->id_placement,
            'tanggal_shift' => $shift->tanggal_shift,
        ]))->toThrow(QueryException::class);
    });

    it('menolak hapus evaluasi yang sudah punya placement (restrict)', function () {
        $placement = Placement::factory()->create();

        expect(fn () => $placement->evaluasi->delete())->toThrow(QueryException::class);
    });

    it('menyimpan status sebagai enum', function () {
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan]);

        expect($job->refresh()->status_req)->toBe(StatusReq::Diajukan);
    });
});
