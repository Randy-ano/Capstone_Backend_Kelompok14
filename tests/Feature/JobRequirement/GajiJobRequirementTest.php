<?php

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Enums\StatusReq;
use App\Models\HrClient;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;

// Kisaran gaji di permintaan kebutuhan (revisi 2026-11-05): gaji_min & gaji_maks opsional, gaji_maks ≥ gaji_min.

function dataJobGaji(array $ubah = []): array
{
    return [
        'posisi' => 'Backend Developer',
        'jumlah_kebutuhan' => 1,
        'sla_deadline' => now()->addDays(30)->toDateTimeString(),
        'kompetensi' => [Kompetensi::factory()->create()->id_kompetensi],
        ...$ubah,
    ];
}

describe('gaji di permintaan kebutuhan', function () {
    it('HR Client dan AM menyimpan kisaran gaji; tanpa gaji tetap boleh', function () {
        loginSebagai(Role::HrClient);
        $this->postJson('/api/v1/job-requirements', dataJobGaji(['gaji_min' => 8000000, 'gaji_maks' => 12000000]))
            ->assertCreated()
            ->assertJsonPath('data.gaji_min', 8000000)
            ->assertJsonPath('data.gaji_maks', 12000000);

        loginSebagai(Role::AccountManager);
        $this->postJson('/api/v1/job-requirements', dataJobGaji(['id_klien' => Klien::factory()->create()->id_klien, 'gaji_min' => 9000000, 'gaji_maks' => 9000000]))
            ->assertCreated()
            ->assertJsonPath('data.gaji_maks', 9000000);
        $this->postJson('/api/v1/job-requirements', dataJobGaji(['id_klien' => Klien::factory()->create()->id_klien]))
            ->assertCreated()
            ->assertJsonPath('data.gaji_min', null)
            ->assertJsonPath('data.gaji_maks', null);
    });

    it('menolak gaji tidak valid (422)', function (array $gaji, string $kolom, ?string $pesan) {
        loginSebagai(Role::HrClient);

        $respons = $this->postJson('/api/v1/job-requirements', dataJobGaji($gaji))->assertUnprocessable()->assertJsonValidationErrors([$kolom]);
        if ($pesan) {
            $respons->assertJsonPath("errors.{$kolom}.0", $pesan);
        }
    })->with([
        'maks < min' => [['gaji_min' => 12000000, 'gaji_maks' => 8000000], 'gaji_maks', 'Gaji maksimal tidak boleh lebih kecil dari gaji minimal.'],
        'negatif' => [['gaji_min' => -1], 'gaji_min', null],
        'bukan angka' => [['gaji_maks' => 'sepuluh juta'], 'gaji_maks', null],
        'terlalu besar' => [['gaji_min' => 1_000_000_000_001], 'gaji_min', null],
    ]);

    it('ubah sebagian: gaji_maks dibandingkan dengan gaji_min yang tersimpan', function () {
        $hr = loginSebagai(Role::HrClient);
        $job = JobRequirement::factory()->create(['id_klien' => $hr->idKlien(), 'status_req' => StatusReq::Draft, 'gaji_min' => 10000000, 'gaji_maks' => 15000000]);

        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['gaji_maks' => 9000000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gaji_maks']);
        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['gaji_min' => 7000000, 'gaji_maks' => 9000000])
            ->assertOk()
            ->assertJsonPath('data.gaji_min', 7000000)
            ->assertJsonPath('data.gaji_maks', 9000000);
        $this->putJson("/api/v1/job-requirements/{$job->id_job_req}", ['gaji_min' => null, 'gaji_maks' => null])
            ->assertOk()
            ->assertJsonPath('data.gaji_min', null);
    });

    it('tampil untuk HR Client, AM, TS, Manager; talent tidak menerima gaji', function () {
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses, 'gaji_min' => 8000000, 'gaji_maks' => 12000000]);
        $hr = HrClient::factory()->create(['id_klien' => $job->id_klien])->user;

        foreach ([$hr, buatUser(Role::AccountManager), buatUser(Role::TalentSupport), buatUser(Role::Manager)] as $user) {
            $this->actingAs($user)->getJson("/api/v1/job-requirements/{$job->id_job_req}")
                ->assertOk()
                ->assertJsonPath('data.gaji_min', 8000000)
                ->assertJsonPath('data.gaji_maks', 12000000);
        }

        // Talent tidak punya akses ke permintaan kebutuhan, dan data pengajuan miliknya tidak memuat gaji.
        $pengajuan = rekrutmenPengajuan($job, StatusPengajuan::Diajukan);
        $this->actingAs($pengajuan->talent->user)->getJson("/api/v1/job-requirements/{$job->id_job_req}")->assertForbidden();
        $isi = $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")->assertOk()->getContent();
        expect($isi)->not->toContain('gaji_min')->not->toContain('gaji_maks');
    });
});
