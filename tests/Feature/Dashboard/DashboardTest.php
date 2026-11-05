<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\LaporanKeluhan;
use App\Models\Placement;
use App\Models\ScreeningTalent;
use App\Models\User;

describe('GET /dashboard/manager (R32)', function () {
    it('angka agregasi sesuai data seeder', function () {
        $this->seed();
        $this->actingAs(User::where('email', 'manager@intellisys.test')->sole());

        $this->getJson('/api/v1/dashboard/manager')
            ->assertOk()
            ->assertJsonPath('data.job_requirement', ['total' => 8, 'per_status' => ['draft' => 1, 'diajukan' => 1, 'diproses' => 3, 'terpenuhi' => 2, 'dibatalkan' => 1]])
            ->assertJsonPath('data.sla', ['terlambat' => 1, 'mendekati' => 1])
            ->assertJsonPath('data.placement_aktif', 5)
            ->assertJsonPath('data.laporan_keluhan_terbuka', 3)
            // 20 talent di pool − 5 ditempatkan − 9 dalam proses.
            ->assertJsonPath('data.kandidat_per_tahapan.0', ['tahap' => 'tersedia', 'label' => 'Tersedia, belum diproses', 'jumlah' => 6])
            // Usulan TS menunggu AM: Bagas (Frontend) & Farhan (Data Analyst); Intan sudah diterima (dokumen berurutan).
            ->assertJsonPath('data.kandidat_per_tahapan.1', ['tahap' => 'diusulkan', 'label' => 'Diusulkan ke AM', 'jumlah' => 2])
            ->assertJsonPath('data.kandidat_per_tahapan.5', ['tahap' => 'ditempatkan', 'label' => 'Ditempatkan', 'jumlah' => 5])
            ->assertJsonCount(12, 'data.tren_placement')
            ->assertJsonPath('data.tren_placement.11.bulan', now()->format('Y-m'))
            ->assertJsonPath('data.tren_placement.10.jumlah', 1)
            ->assertJsonPath('data.tren_placement.0.jumlah', 1);
    });

    it('menghitung tahapan kandidat, SLA, dan tren dengan data terkontrol', function () {
        $this->freezeTime();
        loginSebagai(Role::Manager);

        $job = rekrutmenJob();
        ScreeningTalent::factory()->for($job)->create(['hasil_screening' => HasilScreening::Pending]);
        rekrutmenScreeningLolos($job);
        rekrutmenPengajuan($job);
        rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);
        $diterima = rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);
        rekrutmenInterview($diterima, StatusInterview::Selesai, now()->subDay()->toDateString());
        rekrutmenEvaluasi($diterima, StatusEvaluasi::Diterima);
        JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan, 'sla_deadline' => now()->subHour()]);
        JobRequirement::factory()->create(['status_req' => StatusReq::Terpenuhi, 'sla_deadline' => now()->subHour()]);
        // Placement aktif bulan ini & 3 bulan lalu, selesai 13 bulan lalu (di luar tren).
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString()]);
        Placement::factory()->create(['tanggal_mulai' => now()->subMonths(3)->toDateString()]);
        Placement::factory()->create(['tanggal_mulai' => now()->subMonths(13)->toDateString(), 'status_placement' => StatusPlacement::Selesai]);
        // Laporan untuk placement yang sudah ada, agar factory tidak membuat placement baru.
        LaporanKeluhan::factory()->for($placement)->create(['status_laporan' => StatusLaporan::Baru]);
        LaporanKeluhan::factory()->for($placement)->create(['status_laporan' => StatusLaporan::Diproses]);
        LaporanKeluhan::factory()->for($placement)->create(['status_laporan' => StatusLaporan::Selesai]);

        $data = $this->getJson('/api/v1/dashboard/manager')->assertOk()->json('data');

        expect(collect($data['kandidat_per_tahapan'])->pluck('jumlah', 'tahap')->except('tersedia')->all())
            ->toBe(['diusulkan' => 2, 'diajukan' => 1, 'interview' => 1, 'diterima' => 1, 'ditempatkan' => 2])
            ->and($data['sla'])->toBe(['terlambat' => 1, 'mendekati' => 0])
            ->and($data['placement_aktif'])->toBe(2)
            ->and($data['laporan_keluhan_terbuka'])->toBe(2)
            ->and(collect($data['tren_placement'])->pluck('jumlah')->sum())->toBe(2)
            ->and($data['tren_placement'][8]['jumlah'])->toBe(1)
            ->and($data['tren_placement'][11]['jumlah'])->toBe(1);
    });

    it('menolak selain Manager (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/dashboard/manager')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Manager));
});

describe('GET /dashboard/ringkasan', function () {
    it('mengembalikan kartu sesuai role dari data seeder', function (string $email, array $harapan) {
        $this->seed();
        $this->actingAs(User::where('email', $email)->sole());

        $data = $this->getJson('/api/v1/dashboard/ringkasan')
            ->assertOk()
            ->assertJsonStructure(['data' => ['kartu' => [['kunci', 'label', 'nilai', 'keterangan', 'tujuan']], 'notifikasi_belum_dibaca']])
            ->json('data');

        expect(collect($data['kartu'])->pluck('nilai', 'kunci')->all())->toBe($harapan);
        if ($email === 'ts@intellisys.test') {
            expect($data['kartu'][0]['label'])->toBe('Tersedia, belum diproses');
        }
    })->with([
        'manager' => ['manager@intellisys.test', ['job_aktif' => 4, 'sla_terlambat' => 1, 'placement_aktif' => 5, 'cuti_menunggu' => 2, 'keluhan_terbuka' => 3, 'ttd_menunggu' => 2]],
        'account manager' => ['am@intellisys.test', ['job_aktif' => 4, 'usulan_menunggu' => 2, 'menunggu_klien' => 1, 'interview_mendatang' => 2, 'keluhan_terbuka' => 3, 'ttd_menunggu' => 0]],
        'talent support' => ['ts@intellisys.test', ['talent_tersedia' => 6, 'job_terbuka' => 4, 'usulan_menunggu' => 2, 'menunggu_placement' => 3]],
        'hr client (PT Maju Jaya)' => ['hr@intellisys.test', ['job_aktif' => 1, 'perlu_ditinjau' => 1, 'interview_mendatang' => 1, 'placement_aktif' => 3, 'cuti_menunggu' => 1, 'ttd_menunggu' => 1]],
        'talent (Budi)' => ['talent@intellisys.test', ['status_profil' => 'Lengkap', 'status_kandidat' => 'Ditempatkan', 'interview_mendatang' => 0, 'cuti_menunggu' => 1]],
    ]);

    it('HR Client hanya menghitung data kliennya', function () {
        $placement = Placement::factory()->create();
        Placement::factory()->count(2)->create();
        loginHrKlien($placement);

        $kartu = collect($this->getJson('/api/v1/dashboard/ringkasan')->json('data.kartu'))->pluck('nilai', 'kunci');
        expect($kartu['placement_aktif'])->toBe(1);
    });

    it('menolak tanpa login (401)', function () {
        $this->getJson('/api/v1/dashboard/ringkasan')->assertUnauthorized();
    });
});
