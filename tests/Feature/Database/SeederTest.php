<?php

use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed());

it('membuat 1 akun demo per role dengan password "password"', function (string $email, Role $role, string $profil) {
    $user = User::where('email', $email)->firstOrFail();

    expect($user->role)->toBe($role)
        ->and($user->status_aktif)->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->{$profil})->not->toBeNull();
})->with([
    ['talent@intellisys.test', Role::Talent, 'talent'],
    ['hr@intellisys.test', Role::HrClient, 'hrClient'],
    ['am@intellisys.test', Role::AccountManager, 'pegawai'],
    ['ts@intellisys.test', Role::TalentSupport, 'pegawai'],
    ['manager@intellisys.test', Role::Manager, 'pegawai'],
]);

it('membuat master data dan data contoh sesuai jumlah di spec', function () {
    expect(Kompetensi::count())->toBe(30)
        ->and(Klien::count())->toBe(3)
        ->and(Talent::count())->toBe(20)
        ->and(JobRequirement::count())->toBe(5);
});

it('memasukkan hanya talent berprofil lengkap ke talent pool', function () {
    $lengkap = Talent::where('status_kelengkapan', StatusKelengkapan::Lengkap)->get();

    expect($lengkap)->not->toBeEmpty()
        ->and(Talent::where('status_kelengkapan', StatusKelengkapan::BelumLengkap)->count())->toBeGreaterThan(0)
        ->and(Talent::has('talentPool')->count())->toBe($lengkap->count());

    // Definisi lengkap R4.
    $lengkap->each(fn (Talent $talent) => expect($talent->nama_lengkap)->not->toBeEmpty()
        ->and($talent->riwayatPendidikan)->not->toBeEmpty()
        ->and($talent->kompetensi)->not->toBeEmpty());

    Talent::where('status_kelengkapan', StatusKelengkapan::BelumLengkap)->get()
        ->each(fn (Talent $talent) => expect($talent->riwayatPendidikan->isEmpty() || $talent->kompetensi->isEmpty())->toBeTrue());
});

it('membuat job requirement dengan minimal 1 kompetensi dan log progres untuk yang sudah diajukan', function () {
    JobRequirement::with(['kompetensi', 'progres' => fn ($q) => $q->orderBy('id_progres')])->get()->each(function (JobRequirement $job) {
        expect($job->kompetensi)->not->toBeEmpty();

        if ($job->status_req === StatusReq::Draft) {
            expect($job->progres)->toBeEmpty();
        } else {
            expect($job->progres->first()->status_tahapan)->toBe(StatusTahapan::KebutuhanDibuat);
        }
    });
});

it('membuat screening demo yang konsisten dengan R9–R12', function () {
    $job = JobRequirement::with(['screenings.talent', 'progres'])->where('posisi', 'Backend Developer')->sole();
    $hasil = $job->screenings->mapWithKeys(fn ($s) => [$s->hasil_screening->value => $s]);

    expect($job->status_req)->toBe(StatusReq::Diproses)
        ->and($hasil->keys()->sort()->values()->all())->toBe(['lolos', 'pending', 'tidak_lolos'])
        // Kandidat lolos (Budi) sudah ditempatkan oleh OperasionalSeeder.
        ->and($hasil['lolos']->talent->status_kandidat)->toBe(StatusKandidat::Ditempatkan)
        ->and($hasil['pending']->talent->status_kandidat)->toBe(StatusKandidat::DalamProses)
        ->and($hasil['tidak_lolos']->talent->status_kandidat)->toBe(StatusKandidat::Tersedia)
        ->and((float) $hasil['lolos']->skor_kecocokan)->toBeGreaterThanOrEqual((float) $hasil['tidak_lolos']->skor_kecocokan)
        // kebutuhan_dibuat + 3 screening + 1 ditolak (Fase 4) + diajukan, interview, evaluasi (Fase 5) + placement (Fase 6).
        ->and($job->progres->countBy(fn ($p) => $p->status_tahapan->value)->all())
        ->toBe(['kebutuhan_dibuat' => 1, 'screening' => 3, 'ditolak' => 1, 'diajukan' => 1, 'interview' => 1, 'evaluasi' => 1, 'placement' => 1]);
});

it('membuat demo Fase 5: kandidat lolos diajukan, interview selesai, evaluasi diterima, lalu ditempatkan (Fase 6)', function () {
    $pengajuan = App\Models\PengajuanKandidat::with(['interviews', 'evaluasi.placement', 'talent', 'jobRequirement'])->sole();

    expect($pengajuan->status_pengajuan)->toBe(App\Enums\StatusPengajuan::LanjutInterview)
        ->and($pengajuan->screening->hasil_screening)->toBe(App\Enums\HasilScreening::Lolos)
        ->and($pengajuan->interviews->sole()->status_interview)->toBe(App\Enums\StatusInterview::Selesai)
        ->and($pengajuan->evaluasi->status_evaluasi)->toBe(App\Enums\StatusEvaluasi::Diterima)
        ->and($pengajuan->evaluasi->hrClient->user->email)->toBe('hr@intellisys.test')
        ->and($pengajuan->evaluasi->placement->status_placement)->toBe(App\Enums\StatusPlacement::Aktif)
        ->and($pengajuan->talent->status_kandidat)->toBe(StatusKandidat::Ditempatkan)
        // Kebutuhan 2, baru 1 placement: job tetap diproses.
        ->and($pengajuan->jobRequirement->status_req)->toBe(StatusReq::Diproses);
});

it('membuat demo Fase 6 untuk placement Budi: shift 2 minggu, cuti menunggu, monitoring, dan keluhan baru', function () {
    $placement = App\Models\Placement::with(['shifts', 'cuti', 'monitorings', 'laporanKeluhan', 'talent.user', 'klien'])->sole();

    expect($placement->talent->user->email)->toBe('talent@intellisys.test')
        ->and($placement->klien->nama_perusahaan)->toBe('PT Maju Jaya')
        ->and($placement->shifts)->toHaveCount(14)
        ->and($placement->shifts->where('jenis_shift', App\Enums\JenisShift::Libur))->toHaveCount(4)
        ->and($placement->cuti->sole()->status_cuti)->toBe(App\Enums\StatusCuti::Menunggu)
        ->and($placement->monitorings)->toHaveCount(1)
        ->and($placement->laporanKeluhan->sole()->status_laporan)->toBe(App\Enums\StatusLaporan::Baru)
        ->and($placement->talent->talentPool->status_ketersediaan)->toBe(App\Enums\StatusKetersediaan::TidakTersedia);
});

it('membuat demo Fase 7: quotation menunggu tanda tangan Manager & HR, PO masih draft', function () {
    $dokumen = App\Models\Dokumen::with('tandaTangan.penandatangan')->orderBy('id_dokumen')->get();

    expect($dokumen)->toHaveCount(2)
        ->and($dokumen[0]->jenis_dokumen)->toBe(App\Enums\JenisDokumen::Quotation)
        ->and($dokumen[0]->status_dokumen)->toBe(App\Enums\StatusDokumen::MenungguTtd)
        ->and($dokumen[0]->tandaTangan->map(fn ($t) => $t->penandatangan->email)->sort()->values()->all())->toBe(['hr@intellisys.test', 'manager@intellisys.test'])
        ->and($dokumen[1]->jenis_dokumen)->toBe(App\Enums\JenisDokumen::PurchaseOrder)
        ->and($dokumen[1]->status_dokumen)->toBe(App\Enums\StatusDokumen::Draft);
});
