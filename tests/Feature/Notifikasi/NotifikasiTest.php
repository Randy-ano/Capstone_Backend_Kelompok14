<?php

use App\Enums\HasilScreening;
use App\Enums\KanalNotifikasi;
use App\Enums\Role;
use App\Enums\StatusInterview;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPengiriman;
use App\Enums\StatusReq;
use App\Jobs\KirimEmailNotifikasi;
use App\Mail\NotifikasiMail;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\Notifikasi;
use App\Models\PengajuanCuti;
use App\Models\Placement;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/** Tipe notifikasi yang diterima user. */
function tipeDiterima(User $user): array
{
    return Notifikasi::where('id_user_penerima', $user->id_user)->pluck('tipe_notifikasi')->all();
}

describe('API /notifikasi', function () {
    it('menampilkan notifikasi milik sendiri terbaru dulu, tanpa yang masih dijadwalkan, dan bisa difilter status baca', function () {
        $user = loginSebagai(Role::Talent);
        Notifikasi::factory()->create(['id_user_penerima' => $user->id_user, 'pesan' => 'Lama', 'created_at' => now()->subDay(), 'status_baca' => true]);
        Notifikasi::factory()->create(['id_user_penerima' => $user->id_user, 'pesan' => 'Baru']);
        Notifikasi::factory()->create(['id_user_penerima' => $user->id_user, 'pesan' => 'Nanti', 'jadwal_kirim' => now()->addDay()]);
        Notifikasi::factory()->create();

        $this->getJson('/api/v1/notifikasi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.pesan', 'Baru')
            ->assertJsonStructure(['data' => [['id_notifikasi', 'tipe_notifikasi', 'pesan', 'kanal', 'status_pengiriman', 'status_baca', 'dibuat_pada']], 'links', 'meta']);
        $this->getJson('/api/v1/notifikasi?status_baca=0')->assertJsonCount(1, 'data')->assertJsonPath('data.0.pesan', 'Baru');
        $this->getJson('/api/v1/notifikasi/belum-dibaca/jumlah')->assertExactJson(['data' => ['jumlah' => 1], 'message' => 'Berhasil']);
        $this->getJson('/api/v1/notifikasi?status_baca=x')->assertUnprocessable()->assertJsonValidationErrors(['status_baca']);
    });

    it('menandai satu dan semua notifikasi dibaca, hanya milik sendiri', function () {
        $user = loginSebagai(Role::HrClient);
        $milik = Notifikasi::factory()->count(3)->create(['id_user_penerima' => $user->id_user]);
        $lain = Notifikasi::factory()->create();

        $this->patchJson("/api/v1/notifikasi/{$milik[0]->id_notifikasi}/baca")
            ->assertOk()
            ->assertJsonPath('message', 'Notifikasi ditandai dibaca.')
            ->assertJsonPath('data.status_baca', true);
        $this->patchJson("/api/v1/notifikasi/{$lain->id_notifikasi}/baca")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke notifikasi ini.']);
        $this->patchJson('/api/v1/notifikasi/baca-semua')
            ->assertOk()
            ->assertExactJson(['data' => ['jumlah' => 2], 'message' => '2 notifikasi ditandai dibaca.']);

        expect($lain->fresh()->status_baca)->toBeFalse();
        $this->patchJson('/api/v1/notifikasi/999/baca')->assertNotFound();
    });

    it('menolak tanpa login (401)', function () {
        $this->getJson('/api/v1/notifikasi')->assertUnauthorized();
    });
});

describe('R29: NotifikasiService::kirim', function () {
    it('kanal sistem langsung terkirim; kanal email masuk antrean; berjadwal menunggu', function () {
        Queue::fake();
        $user = buatUser(Role::Talent);
        $layanan = app(NotifikasiService::class);

        expect($layanan->kirim($user, 'uji', 'Halo')->status_pengiriman)->toBe(StatusPengiriman::Terkirim);
        $email = $layanan->kirim($user, 'uji', 'Halo email', KanalNotifikasi::Email);
        expect($email->status_pengiriman)->toBe(StatusPengiriman::Menunggu);
        Queue::assertPushed(KirimEmailNotifikasi::class, fn ($job) => $job->notifikasi->is($email));

        $nanti = $layanan->kirim($user, 'uji', 'Nanti', KanalNotifikasi::Sistem, now()->addHour());
        expect($nanti->fresh()->status_pengiriman)->toBe(StatusPengiriman::Menunggu);
    });

    it('job email mengirim mail lalu menandai terkirim, atau gagal setelah percobaan habis', function () {
        Mail::fake();
        $notifikasi = Notifikasi::factory()->create(['kanal' => KanalNotifikasi::Email]);

        (new KirimEmailNotifikasi($notifikasi))->handle();
        Mail::assertSent(NotifikasiMail::class, fn ($m) => $m->hasTo($notifikasi->penerima->email));
        expect($notifikasi->fresh()->status_pengiriman)->toBe(StatusPengiriman::Terkirim);

        (new KirimEmailNotifikasi($notifikasi))->failed(new RuntimeException('SMTP mati'));
        expect($notifikasi->fresh()->status_pengiriman)->toBe(StatusPengiriman::Gagal);
    });
});

describe('R30: notifikasi otomatis dari event', function () {
    it('pengajuan dibuat → HR Client klien; status berubah → AM & talent', function () {
        $job = rekrutmenJob();
        $screening = rekrutmenScreeningLolos($job);
        $hr = HrClient::factory()->create(['id_klien' => $job->id_klien])->user;
        $am = loginSebagai(Role::AccountManager);

        $id = $this->postJson('/api/v1/pengajuan', ['id_screening' => $screening->id_screening])->assertCreated()->json('data.id_pengajuan');
        expect(tipeDiterima($hr))->toBe(['pengajuan_dibuat']);

        $this->actingAs($hr)->patchJson("/api/v1/pengajuan/{$id}/status", ['status_pengajuan' => 'lanjut_interview'])->assertOk();
        expect(tipeDiterima($am))->toBe(['pengajuan_diperbarui'])
            ->and(tipeDiterima($screening->talent->user))->toBe(['pengajuan_diperbarui'])
            ->and(Notifikasi::where('id_user_penerima', $am->id_user)->value('pesan'))->toContain('dilanjutkan ke tahap interview');
    });

    it('interview dijadwalkan & dijadwal ulang → talent dan HR Client', function () {
        $job = rekrutmenJob();
        $pengajuan = rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);
        $hr = HrClient::factory()->create(['id_klien' => $job->id_klien])->user;
        loginSebagai(Role::AccountManager);

        $id = $this->postJson('/api/v1/interview', ['id_pengajuan' => $pengajuan->id_pengajuan, 'tanggal_interview' => now()->addDays(2)->toDateString(), 'waktu_interview' => '09:30'])
            ->assertCreated()->json('data.id_interview');
        $this->putJson("/api/v1/interview/{$id}", ['waktu_interview' => '13:00'])->assertOk();

        expect(tipeDiterima($pengajuan->talent->user))->toBe(['interview_dijadwalkan', 'interview_diubah'])
            ->and(tipeDiterima($hr))->toBe(['interview_dijadwalkan', 'interview_diubah'])
            ->and(Notifikasi::where('tipe_notifikasi', 'interview_diubah')->value('pesan'))->toContain('dijadwal ulang')->toContain('13:00');
    });

    it('evaluasi → AM & TS; placement → talent & HR Client', function () {
        $job = rekrutmenJob();
        $pengajuan = rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);
        rekrutmenInterview($pengajuan, StatusInterview::Selesai, now()->subDay()->toDateString());
        $hr = HrClient::factory()->create(['id_klien' => $job->id_klien])->user;
        $ts = $pengajuan->screening->talentSupport->user;

        $this->actingAs($hr)->postJson('/api/v1/evaluasi', ['id_pengajuan' => $pengajuan->id_pengajuan, 'nilai_score' => 90, 'status_evaluasi' => 'diterima'])->assertCreated();
        expect(tipeDiterima($pengajuan->accountManager->user))->toBe(['evaluasi_disimpan'])
            ->and(tipeDiterima($ts))->toBe(['evaluasi_disimpan']);

        $this->actingAs($ts)->postJson('/api/v1/placement', ['id_evaluasi' => $pengajuan->evaluasi()->value('id_evaluasi'), 'tanggal_mulai' => now()->toDateString()])->assertCreated();
        expect(tipeDiterima($pengajuan->talent->user))->toBe(['placement_dibuat'])
            ->and(tipeDiterima($hr))->toBe(['placement_dibuat']);
    });

    it('cuti diajukan → HR Client & Manager; keputusan akhir → talent', function () {
        $placement = Placement::factory()->create();
        $hr = HrClient::factory()->create(['id_klien' => $placement->id_klien])->user;
        $manager = buatUser(Role::Manager);
        $talent = loginTalentPlacement($placement);

        $id = $this->postJson('/api/v1/cuti', ['id_placement' => $placement->id_placement, 'tanggal_mulai' => now()->addDays(3)->toDateString(), 'tanggal_selesai' => now()->addDays(3)->toDateString(), 'alasan_cuti' => 'Keluarga.'])
            ->assertCreated()->json('data.id_cuti');
        expect(tipeDiterima($hr))->toBe(['cuti_diajukan'])->and(tipeDiterima($manager))->toBe(['cuti_diajukan']);

        $this->actingAs($hr)->patchJson("/api/v1/cuti/{$id}/approval", ['keputusan' => 'disetujui']);
        expect(tipeDiterima($talent))->toBe([]);
        $this->actingAs($manager)->patchJson("/api/v1/cuti/{$id}/approval", ['keputusan' => 'disetujui']);
        expect(tipeDiterima($talent))->toBe(['cuti_diputuskan'])
            ->and(Notifikasi::where('id_user_penerima', $talent->id_user)->value('pesan'))->toContain('disetujui');
    });

    it('keluhan baru → AM job & Manager; permintaan tanda tangan → penandatangan', function () {
        $placement = Placement::factory()->create();
        $manager = buatUser(Role::Manager);
        loginHrKlien($placement);

        $this->postJson('/api/v1/laporan-keluhan', ['id_placement' => $placement->id_placement, 'judul_laporan' => 'Terlambat', 'deskripsi_keluhan' => 'Sering terlambat.'])->assertCreated();
        expect(tipeDiterima($placement->jobRequirement->accountManager->user))->toBe(['keluhan_baru'])
            ->and(tipeDiterima($manager))->toBe(['keluhan_baru']);

        $am = loginSebagai(Role::AccountManager);
        $dokumen = Dokumen::factory()->create(['id_user_uploader' => $am->id_user]);
        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$manager->id_user]])->assertOk();
        expect(tipeDiterima($manager))->toBe(['keluhan_baru', 'tanda_tangan_diminta']);
    });
});

describe('R31: php artisan notifikasi:pengingat', function () {
    it('mengirim pengingat SLA, interview besok, cuti & tanda tangan tertunda lewat email, lalu notifikasi terjadwal', function () {
        Queue::fake();
        $manager = buatUser(Role::Manager);

        $terlambat = JobRequirement::factory()->create(['status_req' => StatusReq::Diproses, 'sla_deadline' => now()->subDay()]);
        JobRequirement::factory()->create(['status_req' => StatusReq::Terpenuhi, 'sla_deadline' => now()->subDay()]);
        $interview = JadwalInterview::factory()->create(['tanggal_interview' => now()->addDay()->toDateString()]);
        $cuti = PengajuanCuti::factory()->create(['created_at' => now()->subDays(3)]);
        PengajuanCuti::factory()->create(['created_at' => now()->subDay()]);
        $ttd = TandaTanganDokumen::factory()->create(['created_at' => now()->subDays(3), 'id_dokumen' => Dokumen::factory()->create(['status_dokumen' => 'menunggu_ttd'])]);
        $terjadwal = Notifikasi::factory()->create(['kanal' => KanalNotifikasi::Sistem, 'jadwal_kirim' => now()->subMinute()]);

        $this->artisan('notifikasi:pengingat')
            ->expectsOutputToContain('Pengingat terkirim')
            ->assertSuccessful();

        expect(tipeDiterima($manager))->toContain('sla_terlambat', 'pengingat_cuti')
            ->and(tipeDiterima($terlambat->accountManager->user))->toBe(['sla_terlambat'])
            ->and(tipeDiterima($interview->pengajuan->talent->user))->toBe(['pengingat_interview'])
            ->and(tipeDiterima($ttd->penandatangan))->toBe(['pengingat_tanda_tangan'])
            ->and(Notifikasi::where('tipe_notifikasi', 'pengingat_cuti')->where('id_user_penerima', $manager->id_user)->value('pesan'))->toContain($cuti->talent->nama_lengkap)
            ->and(Notifikasi::where('tipe_notifikasi', 'sla_terlambat')->value('kanal'))->toBe(KanalNotifikasi::Email)
            ->and($terjadwal->fresh()->status_pengiriman)->toBe(StatusPengiriman::Terkirim);
        Queue::assertPushed(KirimEmailNotifikasi::class);

        // Idempoten: dijalankan lagi di hari yang sama tidak mengirim ulang.
        $jumlah = Notifikasi::count();
        $this->artisan('notifikasi:pengingat')->assertSuccessful();
        expect(Notifikasi::count())->toBe($jumlah);
    });

    it('terjadwal pukul 07:00 WIB', function () {
        $this->artisan('schedule:list')->expectsOutputToContain('notifikasi:pengingat')->assertSuccessful();
        $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'notifikasi:pengingat'));
        expect($event->expression)->toBe('0 7 * * *')->and($event->timezone)->toBe('Asia/Jakarta');
    });
});
