<?php

use App\Enums\Role;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusKandidat;
use App\Enums\StatusPengajuan;
use App\Enums\StatusTahapan;
use App\Models\EvaluasiKandidat;
use App\Models\JadwalInterview;

function dataInterview(int $idPengajuan, array $ubah = []): array
{
    return [
        'id_pengajuan' => $idPengajuan,
        'tanggal_interview' => now()->addDays(2)->toDateString(),
        'waktu_interview' => '10:00',
        'link_lokasi_meeting' => 'https://meet.google.com/abc-defg-hij',
        'instruksi' => 'Siapkan portofolio.',
        ...$ubah,
    ];
}

describe('GET /interview', function () {
    it('menampilkan interview sesuai scope, urut tanggal, dan bisa difilter', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($hr->idKlien()), StatusPengajuan::LanjutInterview);
        $nanti = rekrutmenInterview($pengajuan, tanggal: now()->addDays(10)->toDateString());
        $dekat = rekrutmenInterview($pengajuan, tanggal: now()->addDay()->toDateString());
        rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview));

        $this->getJson('/api/v1/interview')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id_interview', $dekat->id_interview)
            ->assertJsonPath('data.0.waktu_interview', '10:00')
            ->assertJsonStructure(['data' => [['id_interview', 'tanggal_interview', 'waktu_interview', 'link_lokasi_meeting', 'instruksi', 'status_interview',
                'pengajuan' => ['id_pengajuan', 'status_pengajuan', 'talent' => ['id_talent', 'nama_lengkap'], 'job_requirement' => ['id_job_req', 'posisi', 'klien']]]]]);

        $dari = now()->addDays(5)->toDateString();
        $this->getJson("/api/v1/interview?dari={$dari}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_interview', $nanti->id_interview);
        $this->getJson('/api/v1/interview?dari=2026-12-01&sampai=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonPath('errors.sampai.0', 'Tanggal "sampai" tidak boleh sebelum tanggal "dari".');
    });

    it('talent hanya melihat interview miliknya', function () {
        $user = loginSebagai(Role::Talent);
        rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview, $user->talent));
        rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview));

        $this->getJson('/api/v1/interview')->assertOk()->assertJsonCount(1, 'data');
    });
});

describe('POST /interview', function () {
    it('AM atau HR Client klien sendiri menjadwalkan interview dan progres dicatat (R16, R12)', function (Role $role) {
        $user = loginSebagai($role);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($role === Role::HrClient ? $user->idKlien() : null), StatusPengajuan::LanjutInterview);

        $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan))
            ->assertCreated()
            ->assertJsonPath('message', 'Interview berhasil dijadwalkan.')
            ->assertJsonPath('data.status_interview', 'dijadwalkan')
            ->assertJsonPath('data.waktu_interview', '10:00');

        // Ronde kedua tetap boleh.
        $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan, ['waktu_interview' => '14:30']))->assertCreated();

        expect($pengajuan->interviews()->count())->toBe(2)
            ->and($pengajuan->jobRequirement->progres()->where('status_tahapan', StatusTahapan::Interview)->count())->toBe(2);
    })->with([Role::AccountManager, Role::HrClient]);

    it('menolak pengajuan yang belum lanjut interview atau sudah dievaluasi (422)', function (string $kasus, string $pesan) {
        loginSebagai(Role::AccountManager);
        $pengajuan = match ($kasus) {
            'masih diajukan' => rekrutmenPengajuan(rekrutmenJob()),
            'sudah dievaluasi' => tap(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview), fn ($p) => rekrutmenEvaluasi($p)),
        };

        $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan))
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_pengajuan.0', $pesan);
    })->with([
        ['masih diajukan', 'Interview hanya dapat diatur untuk pengajuan berstatus lanjut interview.'],
        ['sudah dievaluasi', 'Pengajuan ini sudah dievaluasi; jadwal interview tidak dapat diubah.'],
    ]);

    it('menolak data tidak valid (422)', function () {
        loginSebagai(Role::AccountManager);
        $pengajuan = rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview);

        $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan, ['tanggal_interview' => now()->subDay()->toDateString(), 'waktu_interview' => '25:99']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_interview.0', 'Tanggal interview tidak boleh sebelum hari ini.')
            ->assertJsonPath('errors.waktu_interview.0', 'Format waktu interview harus jam:menit, misalnya 10:00.');
    });

    it('menolak HR Client klien lain (403) dan role lain (403)', function () {
        $pengajuan = rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview);

        loginSebagai(Role::HrClient);
        $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan ini.']);

        foreach ([Role::TalentSupport, Role::Manager, Role::Talent] as $role) {
            loginSebagai($role);
            $this->postJson('/api/v1/interview', dataInterview($pengajuan->id_pengajuan))
                ->assertForbidden()
                ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
        }
        expect(JadwalInterview::count())->toBe(0);
    });
});

describe('PUT /interview/{id}', function () {
    it('jadwal ulang mengubah status menjadi dijadwal_ulang', function () {
        loginSebagai(Role::AccountManager);
        $interview = rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview));

        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['tanggal_interview' => now()->addDays(5)->toDateString(), 'waktu_interview' => '13:30'])
            ->assertOk()
            ->assertJsonPath('message', 'Jadwal interview berhasil diperbarui.')
            ->assertJsonPath('data.status_interview', 'dijadwal_ulang')
            ->assertJsonPath('data.waktu_interview', '13:30');

        // Ubah link saja tidak mengubah status.
        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['link_lokasi_meeting' => 'Kantor klien lt. 5'])
            ->assertOk()
            ->assertJsonPath('data.status_interview', 'dijadwal_ulang')
            ->assertJsonPath('data.link_lokasi_meeting', 'Kantor klien lt. 5');
    });

    it('menandai selesai interview yang sudah berlangsung; status akhir tidak dapat diubah lagi', function () {
        $hr = loginSebagai(Role::HrClient);
        $interview = rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob($hr->idKlien()), StatusPengajuan::LanjutInterview), tanggal: now()->toDateString());

        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['status_interview' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('data.status_interview', 'selesai');

        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['status_interview' => 'dibatalkan'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_interview.0', 'Interview yang sudah selesai tidak dapat diubah.');
    });

    it('menolak menandai selesai sebelum waktunya, body kosong, dan gabungan jadwal+status (422)', function (array $body, string $pesan) {
        loginSebagai(Role::AccountManager);
        $interview = rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview), tanggal: now()->addDays(3)->toDateString());

        $this->putJson("/api/v1/interview/{$interview->id_interview}", $body)
            ->assertUnprocessable()
            ->assertJsonPath('errors.status_interview.0', $pesan);
    })->with([
        'belum berlangsung' => [['status_interview' => 'selesai'], 'Interview belum berlangsung, sehingga belum dapat ditandai selesai.'],
        'kosong' => [[], 'Kirim perubahan jadwal atau status interview.'],
        'gabungan' => [['waktu_interview' => '09:00', 'status_interview' => 'dibatalkan'], 'Ubah jadwal dan ubah status interview dilakukan terpisah.'],
    ]);

    it('menolak HR Client klien lain dan role lain (403)', function () {
        $interview = rekrutmenInterview(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview));

        loginSebagai(Role::HrClient);
        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['status_interview' => 'dibatalkan'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke jadwal interview ini.']);

        loginSebagai(Role::TalentSupport);
        $this->putJson("/api/v1/interview/{$interview->id_interview}", ['status_interview' => 'dibatalkan'])->assertForbidden();

        expect($interview->fresh()->status_interview)->toBe(StatusInterview::Dijadwalkan);
    });
});

describe('POST /evaluasi', function () {
    function siapEvaluasi(int $idKlien): App\Models\PengajuanKandidat
    {
        $pengajuan = rekrutmenPengajuan(rekrutmenJob($idKlien), StatusPengajuan::LanjutInterview);
        rekrutmenInterview($pengajuan, StatusInterview::Selesai, now()->toDateString());

        return $pengajuan;
    }

    it('HR Client menerima kandidat; progres evaluasi dicatat', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = siapEvaluasi($hr->idKlien());

        $this->postJson('/api/v1/evaluasi', ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 88, 'catatan_evaluasi' => 'Bagus.', 'status_evaluasi' => 'diterima'])
            ->assertCreated()
            ->assertJsonPath('message', 'Evaluasi berhasil disimpan.')
            ->assertJsonPath('data.nilai_score', 88)
            ->assertJsonPath('data.status_evaluasi', 'diterima')
            ->assertJsonPath('data.hr_client.id_hr_client', $hr->hrClient->id_hr_client);

        expect($pengajuan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::DalamProses)
            ->and($pengajuan->jobRequirement->progres()->latest('id_progres')->first()->status_tahapan)->toBe(StatusTahapan::Evaluasi);
    });

    it('HR Client menolak kandidat: talent kembali tersedia, progres ditolak (R17, R12)', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = siapEvaluasi($hr->idKlien());

        $this->postJson('/api/v1/evaluasi', ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 40, 'status_evaluasi' => 'ditolak'])->assertCreated();

        expect($pengajuan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($pengajuan->jobRequirement->progres()->latest('id_progres')->first()->status_tahapan)->toBe(StatusTahapan::Ditolak);
    });

    it('menolak tanpa interview selesai, evaluasi ganda, dan pengajuan belum lanjut (422)', function (string $kasus, string $pesan) {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = match ($kasus) {
            'belum selesai' => tap(rekrutmenPengajuan(rekrutmenJob($hr->idKlien()), StatusPengajuan::LanjutInterview), fn ($p) => rekrutmenInterview($p)),
            'sudah dievaluasi' => tap(siapEvaluasi($hr->idKlien()), fn ($p) => rekrutmenEvaluasi($p)),
            'masih diajukan' => rekrutmenPengajuan(rekrutmenJob($hr->idKlien())),
        };

        $this->postJson('/api/v1/evaluasi', ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 80, 'status_evaluasi' => 'diterima'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_pengajuan.0', $pesan);
    })->with([
        ['belum selesai', 'Evaluasi dapat diisi setelah minimal 1 interview selesai.'],
        ['sudah dievaluasi', 'Pengajuan ini sudah dievaluasi.'],
        ['masih diajukan', 'Evaluasi hanya untuk pengajuan berstatus lanjut interview.'],
    ]);

    it('menolak nilai di luar 0–100 dan hasil tidak dikenal (422)', function () {
        $hr = loginSebagai(Role::HrClient);
        $pengajuan = siapEvaluasi($hr->idKlien());

        $this->postJson('/api/v1/evaluasi', ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 101, 'status_evaluasi' => 'lulus'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nilai_score', 'status_evaluasi']);
    });

    it('menolak HR Client klien lain (403) dan selain HR Client (403)', function () {
        $pengajuan = siapEvaluasi(App\Models\Klien::factory()->create()->id_klien);
        $body = ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 80, 'status_evaluasi' => 'diterima'];

        loginSebagai(Role::HrClient);
        $this->postJson('/api/v1/evaluasi', $body)->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke pengajuan ini.']);

        foreach ([Role::AccountManager, Role::TalentSupport, Role::Manager, Role::Talent] as $role) {
            loginSebagai($role);
            $this->postJson('/api/v1/evaluasi', $body)->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
        }
        expect(EvaluasiKandidat::count())->toBe(0);
    });
});

describe('GET /evaluasi/{id}', function () {
    it('internal dan HR Client klien sendiri boleh melihat; klien lain & talent ditolak', function () {
        $evaluasi = rekrutmenEvaluasi(rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview));

        loginSebagai(Role::TalentSupport);
        $this->getJson("/api/v1/evaluasi/{$evaluasi->id_evaluasi}")
            ->assertOk()
            ->assertJsonPath('data.status_evaluasi', 'diterima')
            ->assertJsonPath('data.pengajuan.id_pengajuan', $evaluasi->id_pengajuan);

        $this->actingAs($evaluasi->hrClient->user)->getJson("/api/v1/evaluasi/{$evaluasi->id_evaluasi}")->assertOk();

        loginSebagai(Role::HrClient);
        $this->getJson("/api/v1/evaluasi/{$evaluasi->id_evaluasi}")->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke evaluasi ini.']);

        loginSebagai(Role::Talent);
        $this->getJson("/api/v1/evaluasi/{$evaluasi->id_evaluasi}")->assertForbidden();
    });
});
