<?php

use App\Enums\HasilScreening;
use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\Kompetensi;
use App\Models\Notifikasi;
use App\Models\Pegawai;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\TalentPool;
use App\Models\User;

/**
 * Revisi alur 2026-11-05: Talent Support mengusulkan kandidat ke AM, AM meneruskan ke klien atau tidak meneruskan.
 */

/** Job diproses (AM penanggung jawab dari factory) + talent pool tersedia yang cocok 1 dari 4 kompetensi (skor 0,8 × 25 + 0,2 × 100 = 40). */
function siapUsulan(array $atributJob = []): array
{
    $kompetensi = Kompetensi::factory()->count(4)->create();
    $job = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses, 'pengalaman_min' => 0, ...$atributJob]);
    $job->kompetensi()->attach($kompetensi->pluck('id_kompetensi'));
    $talent = Talent::factory()->lengkap()->create();
    $talent->kompetensi()->attach($kompetensi->first());
    TalentPool::factory()->for($talent)->create();

    return [$job, $talent];
}

function tipeNotifikasi(User $user): array
{
    return Notifikasi::where('id_user_penerima', $user->id_user)->pluck('tipe_notifikasi')->all();
}

describe('POST /screening (usulan kandidat)', function () {
    it('mengusulkan talent di luar rekomendasi (skor < 70) dengan catatan dan menotifikasi AM penanggung jawab', function () {
        $ts = loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapUsulan();
        $amLain = buatUser(Role::AccountManager);

        $this->postJson('/api/v1/screening', [
            'id_job_req' => $job->id_job_req,
            'id_talent' => $talent->id_talent,
            'catatan_screening' => 'Lulusan kampus mitra klien; kompetensi lain bisa dilatih.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Kandidat berhasil diusulkan ke Account Manager.')
            ->assertJsonPath('data.skor_kecocokan', 40)
            ->assertJsonPath('data.hasil_screening', 'lolos')
            ->assertJsonPath('data.status_usulan', 'menunggu_am')
            ->assertJsonPath('data.label_status_usulan', 'Menunggu Tinjauan AM')
            ->assertJsonPath('data.catatan_screening', 'Lulusan kampus mitra klien; kompetensi lain bisa dilatih.')
            ->assertJsonPath('data.alasan_tidak_diteruskan', null)
            ->assertJsonPath('data.talent_support.id_pegawai', $ts->pegawai->id_pegawai);

        $notif = Notifikasi::where('id_user_penerima', $job->accountManager->id_user)->sole();
        expect($notif->tipe_notifikasi)->toBe('usulan_kandidat')
            ->and($notif->pesan)->toContain($talent->nama_lengkap)->toContain($job->posisi)
            ->and(tipeNotifikasi($amLain))->toBe([]);
    });

    it('menotifikasi semua AM aktif jika permintaan belum punya AM (dibuat HR Client)', function () {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapUsulan(['id_am' => null]);
        $am1 = buatUser(Role::AccountManager);
        $am2 = buatUser(Role::AccountManager);
        $amNonaktif = buatUser(Role::AccountManager);
        $amNonaktif->update(['status_aktif' => false]);

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])->assertCreated();

        expect(tipeNotifikasi($am1))->toBe(['usulan_kandidat'])
            ->and(tipeNotifikasi($am2))->toBe(['usulan_kandidat'])
            ->and(tipeNotifikasi($amNonaktif))->toBe([]);
    });

    it('menolak usulan ganda untuk permintaan yang sama, termasuk yang sudah tidak diteruskan (422)', function (HasilScreening $hasil) {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapUsulan();
        ScreeningTalent::factory()->for($job)->for($talent)->create(['hasil_screening' => $hasil]);

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_talent.0', 'Talent ini sudah pernah diusulkan untuk permintaan kebutuhan ini.');
    })->with([HasilScreening::Lolos, HasilScreening::TidakLolos]);

    it('membatasi catatan untuk AM maksimal 1000 karakter (422)', function () {
        loginSebagai(Role::TalentSupport);
        [$job, $talent] = siapUsulan();

        $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent, 'catatan_screening' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatan_screening']);
    });
});

describe('GET /screening?status_usulan', function () {
    it('memfilter usulan menunggu AM, diteruskan, dan tidak diteruskan', function () {
        loginSebagai(Role::AccountManager);
        $job = rekrutmenJob();
        $menunggu = rekrutmenScreeningLolos($job);
        ScreeningTalent::factory()->for($job)->create(['hasil_screening' => HasilScreening::Pending]);
        $diteruskan = rekrutmenPengajuan($job)->screening;
        $ditolak = ScreeningTalent::factory()->for($job)->create([
            'hasil_screening' => HasilScreening::TidakLolos,
            'catatan_screening' => ScreeningTalent::gabungCatatan('Catatan TS.', 'Klien minta S1.'),
        ]);

        $this->getJson('/api/v1/screening?status_usulan=menunggu_am')->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status_usulan', 'menunggu_am');
        $this->getJson('/api/v1/screening?status_usulan=diteruskan')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_screening', $diteruskan->id_screening)
            ->assertJsonPath('data.0.label_status_usulan', 'Diteruskan ke Klien');
        $this->getJson('/api/v1/screening?status_usulan=tidak_diteruskan')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_screening', $ditolak->id_screening)
            ->assertJsonPath('data.0.catatan_screening', 'Catatan TS.')
            ->assertJsonPath('data.0.alasan_tidak_diteruskan', 'Klien minta S1.');
        $this->getJson('/api/v1/screening?status_usulan=lainnya')->assertUnprocessable()->assertJsonValidationErrors(['status_usulan']);

        expect($menunggu->id_screening)->toBeInt();
    });
});

describe('PATCH /screening/{id}/tidak-diteruskan', function () {
    it('AM tidak meneruskan usulan: alasan tersimpan, talent kembali tersedia, progres ditolak, TS dinotifikasi', function () {
        $ts = Pegawai::factory()->talentSupport()->create();
        loginSebagai(Role::AccountManager);
        $job = rekrutmenJob();
        $usulan = rekrutmenScreeningLolos($job);
        $usulan->update(['id_ts' => $ts->id_pegawai, 'catatan_screening' => 'Cocok untuk tim data.']);

        $this->patchJson("/api/v1/screening/{$usulan->id_screening}/tidak-diteruskan", ['alasan' => 'Klien mensyaratkan S1 Statistika.'])
            ->assertOk()
            ->assertJsonPath('message', 'Usulan tidak diteruskan ke klien.')
            ->assertJsonPath('data.hasil_screening', 'tidak_lolos')
            ->assertJsonPath('data.status_usulan', 'tidak_diteruskan')
            ->assertJsonPath('data.catatan_screening', 'Cocok untuk tim data.')
            ->assertJsonPath('data.alasan_tidak_diteruskan', 'Klien mensyaratkan S1 Statistika.')
            ->assertJsonPath('data.talent.status_kandidat', 'tersedia');

        expect($usulan->talent->fresh()->status_kandidat)->toBe(StatusKandidat::Tersedia)
            ->and($job->progres()->latest('id_progres')->first()->status_tahapan)->toBe(StatusTahapan::Ditolak)
            ->and(Notifikasi::where('id_user_penerima', $ts->id_user)->sole())
            ->tipe_notifikasi->toBe('usulan_tidak_diteruskan')
            ->pesan->toContain('Klien mensyaratkan S1 Statistika.');
    });

    it('talent yang tidak diteruskan bisa diusulkan untuk permintaan lain', function () {
        [$job, $talent] = siapUsulan();
        $lain = JobRequirement::factory()->create(['status_req' => StatusReq::Diajukan]);
        $lain->kompetensi()->attach($job->kompetensi->first());

        Laravel\Sanctum\Sanctum::actingAs(buatUser(Role::TalentSupport));
        $id = $this->postJson('/api/v1/screening', ['id_job_req' => $job->id_job_req, 'id_talent' => $talent->id_talent])->json('data.id_screening');
        $this->postJson('/api/v1/screening', ['id_job_req' => $lain->id_job_req, 'id_talent' => $talent->id_talent])
            ->assertUnprocessable()->assertJsonValidationErrors(['id_talent']);

        Laravel\Sanctum\Sanctum::actingAs(buatUser(Role::AccountManager));
        $this->patchJson("/api/v1/screening/{$id}/tidak-diteruskan", ['alasan' => 'Tidak sesuai budaya tim.'])->assertOk();

        Laravel\Sanctum\Sanctum::actingAs(buatUser(Role::TalentSupport));
        $this->postJson('/api/v1/screening', ['id_job_req' => $lain->id_job_req, 'id_talent' => $talent->id_talent])->assertCreated();
    });

    it('menolak usulan yang sudah diteruskan, sudah tidak diteruskan, atau job selesai (422)', function (string $kasus, string $pesan) {
        loginSebagai(Role::AccountManager);
        $usulan = match ($kasus) {
            'diteruskan' => rekrutmenPengajuan(rekrutmenJob())->screening,
            'tidak diteruskan' => ScreeningTalent::factory()->for(rekrutmenJob())->create(['hasil_screening' => HasilScreening::TidakLolos]),
            'job dibatalkan' => rekrutmenScreeningLolos(rekrutmenJob(atribut: ['status_req' => StatusReq::Dibatalkan])),
        };

        $this->patchJson("/api/v1/screening/{$usulan->id_screening}/tidak-diteruskan", ['alasan' => 'Alasan.'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.alasan.0', $pesan);
    })->with([
        ['diteruskan', 'Usulan ini sudah diteruskan ke klien.'],
        ['tidak diteruskan', 'Usulan ini sudah tidak diteruskan.'],
        ['job dibatalkan', 'Permintaan kebutuhan sudah dibatalkan; usulan tidak dapat diubah.'],
    ]);

    it('mewajibkan alasan (maks 1000 karakter)', function (mixed $alasan) {
        loginSebagai(Role::AccountManager);
        $usulan = rekrutmenScreeningLolos(rekrutmenJob());

        $this->patchJson("/api/v1/screening/{$usulan->id_screening}/tidak-diteruskan", ['alasan' => $alasan])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alasan']);
    })->with(['kosong' => [''], 'terlalu panjang' => [str_repeat('a', 1001)]]);

    it('menolak selain Account Manager (403)', function (Role $role) {
        loginSebagai($role);
        $usulan = rekrutmenScreeningLolos(rekrutmenJob());

        $this->patchJson("/api/v1/screening/{$usulan->id_screening}/tidak-diteruskan", ['alasan' => 'Alasan.'])->assertForbidden();

        expect($usulan->fresh()->hasil_screening)->toBe(HasilScreening::Lolos);
    })->with(roleSelain(Role::AccountManager));

    it('mengubah catatan TS setelah tidak diteruskan tetap menyimpan alasan AM', function () {
        loginSebagai(Role::TalentSupport);
        $usulan = ScreeningTalent::factory()->for(rekrutmenJob())->create([
            'hasil_screening' => HasilScreening::TidakLolos,
            'catatan_screening' => ScreeningTalent::gabungCatatan('Lama.', 'Kurang pengalaman.'),
        ]);

        $this->putJson("/api/v1/screening/{$usulan->id_screening}", ['catatan_screening' => 'Baru.'])
            ->assertOk()
            ->assertJsonPath('data.catatan_screening', 'Baru.')
            ->assertJsonPath('data.alasan_tidak_diteruskan', 'Kurang pengalaman.');
    });
});

describe('POST /pengajuan (meneruskan usulan)', function () {
    it('AM meneruskan usulan TS ke klien; status usulan menjadi diteruskan', function () {
        loginSebagai(Role::AccountManager);
        $usulan = rekrutmenScreeningLolos(rekrutmenJob());

        $this->postJson('/api/v1/pengajuan', ['id_screening' => $usulan->id_screening])->assertCreated();

        $this->getJson("/api/v1/screening?id_job_req={$usulan->id_job_req}")
            ->assertJsonPath('data.0.status_usulan', 'diteruskan');
    });
});

describe('notifikasi permintaan kebutuhan diajukan', function () {
    it('HR Client mengajukan → semua Talent Support aktif & semua AM (belum ada AM penanggung jawab)', function () {
        $ts1 = buatUser(Role::TalentSupport);
        $ts2 = buatUser(Role::TalentSupport);
        $am = buatUser(Role::AccountManager);
        $manager = buatUser(Role::Manager);
        $hr = loginSebagai(Role::HrClient);

        $this->postJson('/api/v1/job-requirements', [
            'posisi' => 'Data Engineer',
            'jumlah_kebutuhan' => 2,
            'sla_deadline' => now()->addDays(14)->toDateTimeString(),
            'status_req' => 'diajukan',
            'kompetensi' => [Kompetensi::factory()->create()->id_kompetensi],
        ])->assertCreated();

        expect(tipeNotifikasi($ts1))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($ts2))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($am))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($manager))->toBe([])
            ->and(tipeNotifikasi($hr))->toBe([])
            ->and(Notifikasi::where('id_user_penerima', $ts1->id_user)->value('pesan'))->toContain('Data Engineer');
    });

    it('AM mengajukan draft → Talent Support saja (AM pelaku tidak dinotifikasi), draft tidak memicu notifikasi', function () {
        $ts = buatUser(Role::TalentSupport);
        $am = loginSebagai(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft, 'id_am' => $am->pegawai->id_pegawai, 'sla_deadline' => now()->addWeek()]);
        expect(tipeNotifikasi($ts))->toBe([]);

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])->assertOk();

        expect(tipeNotifikasi($ts))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($am))->toBe([]);
    });

    it('HR Client mengajukan draft milik AM → TS & AM penanggung jawab', function () {
        $ts = buatUser(Role::TalentSupport);
        $amLain = buatUser(Role::AccountManager);
        $job = JobRequirement::factory()->create(['status_req' => StatusReq::Draft, 'sla_deadline' => now()->addWeek()]);
        $hr = App\Models\HrClient::factory()->create(['id_klien' => $job->id_klien])->user;
        Laravel\Sanctum\Sanctum::actingAs($hr);

        $this->patchJson("/api/v1/job-requirements/{$job->id_job_req}/status", ['status_req' => 'diajukan'])->assertOk();

        expect(tipeNotifikasi($ts))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($job->accountManager->user))->toBe(['permintaan_diajukan'])
            ->and(tipeNotifikasi($amLain))->toBe([]);
    });
});
