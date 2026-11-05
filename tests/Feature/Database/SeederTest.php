<?php

use App\Enums\HasilScreening;
use App\Enums\IndikatorSla;
use App\Enums\Role;
use App\Enums\StatusCuti;
use App\Enums\StatusDokumen;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\LaporanKeluhan;
use App\Models\MonitoringTalent;
use App\Models\Notifikasi;
use App\Models\PengajuanCuti;
use App\Models\PengajuanKandidat;
use App\Models\Pegawai;
use App\Models\Placement;
use App\Models\ScreeningTalent;
use App\Models\ShiftingTalent;
use App\Models\Talent;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ProgresService;
use App\Services\RekomendasiService;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed());

it('mempertahankan akun demo dan identitasnya', function (string $email, Role $role, string $nama) {
    $user = User::where('email', $email)->firstOrFail();

    expect($user->role)->toBe($role)
        ->and($user->status_aktif)->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->namaTampilan())->toBe($nama);
})->with([
    ['talent@intellisys.test', Role::Talent, 'Budi Santoso'],
    ['hr@intellisys.test', Role::HrClient, 'Sari Wulandari'],
    ['am@intellisys.test', Role::AccountManager, 'Andi Pratama'],
    ['ts@intellisys.test', Role::TalentSupport, 'Rina Kartika'],
    ['manager@intellisys.test', Role::Manager, 'Hendra Gunawan'],
]);

it('membuat volume data sesuai kebutuhan demo', function () {
    expect(Kompetensi::count())->toBe(30)
        ->and(Klien::count())->toBe(4)
        ->and(Pegawai::count())->toBe(5)
        ->and(Talent::where('status_kelengkapan', StatusKelengkapan::Lengkap)->count())->toBe(20)
        ->and(Talent::where('status_kelengkapan', StatusKelengkapan::BelumLengkap)->count())->toBe(2)
        ->and(JobRequirement::count())->toBe(8)
        ->and(Klien::withCount('hrClients')->get()->pluck('hr_clients_count')->every(fn (int $n) => $n >= 1 && $n <= 2))->toBeTrue()
        ->and(User::where('email', 'hr@intellisys.test')->first()->hrClient->klien->nama_perusahaan)->toBe('PT Maju Jaya');
});

it('memakai nama tanpa gelar dan latar pendidikan & pengalaman IT', function () {
    Talent::with(['riwayatPendidikan', 'pengalamanKerjaTalent'])->where('status_kelengkapan', StatusKelengkapan::Lengkap)->get()
        ->each(function (Talent $talent) {
            expect($talent->nama_lengkap)->not->toMatch('/\b(S\.|M\.|Dr\.|Ir\.|Drs\.|H\.)/')
                ->and($talent->pengalamanKerjaTalent)->not->toBeEmpty()
                ->and($talent->riwayatPendidikan->first()->jurusan)->toMatch('/Informatika|Sistem Informasi|Ilmu Komputer|Teknik Komputer/');
        });
});

it('mencakup semua status permintaan dan ketiga indikator SLA', function () {
    $jobs = JobRequirement::all();
    $progres = app(ProgresService::class);

    expect($jobs->map(fn ($j) => $j->status_req)->unique()->count())->toBe(count(StatusReq::cases()))
        ->and($jobs->map(fn ($j) => $progres->indikatorSla($j))->unique()->count())->toBe(count(IndikatorSla::cases()));
});

it('tetap bermakna kapan pun seeder dijalankan: tanggal relatif terhadap hari seed', function () {
    $sla = fn (string $posisi) => JobRequirement::where('posisi', $posisi)->sole()->sla_deadline->startOfDay();

    expect($sla('QA Engineer')->equalTo(now()->startOfDay()->subDays(3)))->toBeTrue()
        ->and($sla('Frontend Developer')->equalTo(now()->startOfDay()->addDays(2)))->toBeTrue()
        ->and($sla('Data Analyst')->equalTo(now()->startOfDay()->addDays(25)))->toBeTrue();
});

it('menempatkan kandidat di setiap tahap', function () {
    // Usulan TS: menunggu AM, diteruskan ke klien, dan tidak diteruskan (dengan alasan); semua usulan berisi catatan.
    $usulan = ScreeningTalent::with('pengajuan')->get();
    expect($usulan->map->statusUsulan()->unique()->sort()->values()->all())->toBe(['diteruskan', 'menunggu_am', 'tidak_diteruskan'])
        ->and($usulan->every(fn (ScreeningTalent $s) => filled($s->catatanTs())))->toBeTrue()
        ->and($usulan->filter(fn (ScreeningTalent $s) => $s->statusUsulan() === 'tidak_diteruskan')->sole()->alasanTidakDiteruskan())->not->toBeEmpty()
        ->and($usulan->where('hasil_screening', HasilScreening::Pending))->toBeEmpty();

    $status = PengajuanKandidat::pluck('status_pengajuan');
    foreach (StatusPengajuan::cases() as $s) {
        expect($status->contains($s))->toBeTrue();
    }

    $tampil = PengajuanKandidat::with('evaluasi.placement')->get()->map(fn ($p) => $p->statusTampil()->value)->unique()->sort()->values()->all();
    expect($tampil)->toBe(['diajukan', 'ditempatkan', 'diterima', 'ditolak', 'lanjut_interview']);

    $interview = JadwalInterview::pluck('status_interview');
    foreach ([StatusInterview::Dijadwalkan, StatusInterview::Selesai, StatusInterview::DijadwalUlang, StatusInterview::Dibatalkan] as $s) {
        expect($interview->contains($s))->toBeTrue();
    }
    expect(JadwalInterview::whereIn('status_interview', [StatusInterview::Dijadwalkan, StatusInterview::DijadwalUlang])->whereDate('tanggal_interview', '>=', now()->toDateString())->count())->toBeGreaterThan(0);
});

it('konsisten dengan aturan: profil, talent pool, dan status kandidat', function () {
    Talent::with(['talentPool', 'riwayatPendidikan', 'kompetensi', 'placements'])->get()->each(function (Talent $t) {
        $lengkap = $t->status_kelengkapan === StatusKelengkapan::Lengkap;
        $aktif = $t->placements->where('status_placement', StatusPlacement::Aktif)->count();

        expect($lengkap)->toBe($t->riwayatPendidikan->isNotEmpty() && $t->kompetensi->isNotEmpty())
            ->and($t->talentPool !== null)->toBe($lengkap)
            ->and($aktif)->toBeLessThanOrEqual(1)
            ->and($t->status_kandidat === StatusKandidat::Ditempatkan)->toBe($aktif === 1);

        if ($t->talentPool) {
            expect($t->talentPool->status_ketersediaan)->toBe($aktif === 1 ? StatusKetersediaan::TidakTersedia : StatusKetersediaan::Tersedia);
        }
    });
});

it('konsisten dengan aturan: urutan tahap rekrutmen', function () {
    ScreeningTalent::with(['talent.talentPool', 'pengajuan'])->get()->each(function (ScreeningTalent $s) {
        expect($s->talent->talentPool)->not->toBeNull();
        if ($s->pengajuan) {
            expect($s->hasil_screening)->toBe(HasilScreening::Lolos);
        }
    });

    PengajuanKandidat::with(['interviews', 'evaluasi.placement'])->get()->each(function (PengajuanKandidat $p) {
        if ($p->interviews->isNotEmpty()) {
            expect($p->status_pengajuan)->toBe(StatusPengajuan::LanjutInterview);
        }
        if ($p->evaluasi) {
            expect($p->interviews->contains(fn ($i) => $i->status_interview === StatusInterview::Selesai))->toBeTrue();
        }
        if ($p->evaluasi?->placement) {
            expect($p->evaluasi->status_evaluasi)->toBe(StatusEvaluasi::Diterima);
        }
    });
});

it('konsisten dengan aturan: jumlah terpenuhi dan log progres', function () {
    $urutan = [
        StatusTahapan::Screening->value => 1,
        StatusTahapan::Diajukan->value => 2,
        StatusTahapan::Interview->value => 3,
        StatusTahapan::Evaluasi->value => 4,
        StatusTahapan::Placement->value => 5,
    ];

    JobRequirement::with(['placements', 'progres' => fn ($q) => $q->orderBy('log_waktu_pembaruan')->orderBy('id_progres')])->get()->each(function (JobRequirement $job) use ($urutan) {
        $aktif = $job->placements->where('status_placement', StatusPlacement::Aktif)->count();

        if ($job->status_req === StatusReq::Terpenuhi) {
            expect($aktif)->toBeGreaterThanOrEqual($job->jumlah_kebutuhan);
        } else {
            expect($aktif)->toBeLessThan($job->jumlah_kebutuhan);
        }

        if ($job->status_req === StatusReq::Draft) {
            expect($job->progres)->toBeEmpty();

            return;
        }
        expect($job->progres->first()->status_tahapan)->toBe(StatusTahapan::KebutuhanDibuat);

        // Per talent: tahap maju sesuai urutan menurut waktu log.
        $job->progres->whereNotNull('id_talent')->groupBy('id_talent')->each(function ($log) use ($urutan) {
            $tertinggi = 0;
            foreach ($log as $l) {
                $nilai = $urutan[$l->status_tahapan->value] ?? null;
                if ($nilai !== null) {
                    expect($nilai)->toBeGreaterThanOrEqual($tertinggi);
                    $tertinggi = $nilai;
                }
            }
        });
    });
});

it('membuat 6 penempatan aktif (1 Belum Aktif) dan 1 selesai yang tersebar, dan grafik tren terisi', function () {
    expect(Placement::where('status_placement', StatusPlacement::Aktif)->count())->toBe(6)
        ->and(Placement::where('status_placement', StatusPlacement::Selesai)->count())->toBe(1)
        ->and(Placement::all()->map(fn (Placement $p) => $p->statusTampil()->value)->countBy()->all())
        ->toMatchArray(['aktif' => 5, 'belum_aktif' => 1, 'selesai' => 1]);

    // Tren 12 bulan menurut bulan mulai; penempatan Nadia mulai minggu depan bisa jatuh di bulan berikutnya.
    $tren = collect(app(DashboardService::class)->trenPlacement())->pluck('jumlah');
    expect($tren->sum())->toBeGreaterThanOrEqual(6)->and($tren->filter()->count())->toBeGreaterThanOrEqual(6);

    // Talent dari penempatan yang sudah selesai kembali tersedia.
    $selesai = Placement::where('status_placement', StatusPlacement::Selesai)->sole()->talent;
    expect($selesai->status_kandidat)->toBe(StatusKandidat::Tersedia)
        ->and($selesai->talentPool->status_ketersediaan)->toBe(StatusKetersediaan::Tersedia);

    // Budi: ditempatkan di PT Maju Jaya dengan riwayat seleksi.
    $budi = User::where('email', 'talent@intellisys.test')->first()->talent;
    $placementBudi = $budi->placements()->with('klien')->sole();
    expect($placementBudi->status_placement)->toBe(StatusPlacement::Aktif)
        ->and($placementBudi->klien->nama_perusahaan)->toBe('PT Maju Jaya')
        ->and($budi->pengajuanKandidat)->not->toBeEmpty();
});

it('menyediakan beberapa kandidat skor ≥ 70 untuk minimal 2 permintaan berjalan', function () {
    $rekomendasi = app(RekomendasiService::class);

    $bervariasi = JobRequirement::whereIn('status_req', [StatusReq::Diajukan, StatusReq::Diproses])->get()
        ->filter(function (JobRequirement $job) use ($rekomendasi) {
            $skor = $rekomendasi->daftar($job)->pluck('skor_kecocokan');

            return $skor->count() >= 2 && $skor->min() >= RekomendasiService::SKOR_MINIMUM;
        });

    expect($bervariasi->count())->toBeGreaterThanOrEqual(2);
});

it('mencakup status cuti, shift beberapa minggu, monitoring, dan tiket', function () {
    $cuti = PengajuanCuti::get();
    foreach (StatusCuti::cases() as $s) {
        expect($cuti->pluck('status_cuti')->contains($s))->toBeTrue();
    }
    // Keputusan cuti hanya HR Client: tidak ada lagi cuti "menunggu manager", kolom approval manager tidak dipakai.
    expect($cuti->contains(fn ($c) => $c->status_cuti === StatusCuti::Menunggu && $c->status_approval_klien->value !== 'menunggu'))->toBeFalse()
        ->and($cuti->whereIn('status_cuti', [StatusCuti::Disetujui, StatusCuti::Ditolak])->every(fn ($c) => $c->status_approval_klien->value === $c->status_cuti->value && $c->id_approver_klien !== null))->toBeTrue()
        ->and($cuti->every(fn ($c) => $c->status_approval_manager->value === 'menunggu' && $c->id_approver_manager === null))->toBeTrue()
        ->and($cuti->every(fn ($c) => ! $c->tanggal_mulai->isWeekend() && ! $c->tanggal_selesai->isWeekend()))->toBeTrue();

    expect(ShiftingTalent::where('status_shift', 'dibatalkan')->count())->toBe(1);
    expect(ShiftingTalent::count())->toBeGreaterThanOrEqual(5 * 7 * 3)
        ->and(MonitoringTalent::count())->toBeGreaterThanOrEqual(5);

    // Monitoring per talent: catatan mayoritas dari HR Client, satu SP (Yoga) dengan dokumen Surat Peringatan.
    $monitoring = MonitoringTalent::with(['pencatat', 'dokumen', 'placement.talent'])->get();
    $sp = $monitoring->where('tipe', App\Enums\TipeMonitoring::Sp)->sole();
    expect($monitoring->filter(fn ($m) => $m->pencatat?->role === Role::HrClient)->count())->toBeGreaterThanOrEqual(5)
        ->and($monitoring->every(fn ($m) => $m->id_user_pencatat !== null && $m->id_manager === null))->toBeTrue()
        ->and($sp->placement->talent->nama_lengkap)->toBe('Yoga Pratama')
        ->and($sp->pencatat->role)->toBe(Role::Manager)
        ->and($sp->dokumen->jenis_dokumen)->toBe(App\Enums\JenisDokumen::SuratPeringatan);

    foreach (StatusLaporan::cases() as $s) {
        expect(LaporanKeluhan::pluck('status_laporan')->contains($s))->toBeTrue();
    }
});

it('mencakup jenis & status dokumen dan tanda tangan yang menunggu manager, HR, serta talent', function () {
    $status = Dokumen::pluck('status_dokumen');
    foreach (StatusDokumen::cases() as $s) {
        expect($status->contains($s))->toBeTrue();
    }
    expect(Dokumen::pluck('jenis_dokumen')->unique()->count())->toBeGreaterThanOrEqual(5);

    foreach (['manager', 'hr', 'talent'] as $akun) {
        $user = User::where('email', "{$akun}@intellisys.test")->first();
        $menunggu = $user->tandaTangan()->where('status_ttd', StatusTtd::Menunggu)
            ->whereHas('dokumen', fn ($q) => $q->where('status_dokumen', StatusDokumen::MenungguTtd))->count();
        expect($menunggu)->toBeGreaterThanOrEqual(1);
    }
});

it('memberi 3–6 notifikasi belum dibaca untuk setiap akun demo', function (string $akun) {
    $user = User::where('email', "{$akun}@intellisys.test")->first();
    $belum = Notifikasi::where('id_user_penerima', $user->id_user)->where('status_baca', false)->count();

    expect($belum)->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(6);
})->with(['manager', 'am', 'ts', 'hr', 'talent']);

it('tidak memakai istilah job requirement di notifikasi', function () {
    expect(Notifikasi::where('pesan', 'like', '%job requirement%')->exists())->toBeFalse();
});

it('membuat setiap jenis waktu sebelum atau pada hari seed', function () {
    expect(Notifikasi::where('created_at', '>', now())->exists())->toBeFalse()
        ->and(ScreeningTalent::where('tanggal_screening', '>', now())->exists())->toBeFalse();
});
