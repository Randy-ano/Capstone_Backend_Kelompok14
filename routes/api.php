<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CutiController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DokumenController;
use App\Http\Controllers\Api\V1\EvaluasiController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InterviewController;
use App\Http\Controllers\Api\V1\JobRequirementController;
use App\Http\Controllers\Api\V1\KlienController;
use App\Http\Controllers\Api\V1\KompetensiController;
use App\Http\Controllers\Api\V1\LaporanKeluhanController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PegawaiController;
use App\Http\Controllers\Api\V1\PengajuanController;
use App\Http\Controllers\Api\V1\PlacementController;
use App\Http\Controllers\Api\V1\ProgresController;
use App\Http\Controllers\Api\V1\ScreeningController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Controllers\Api\V1\TalentCvController;
use App\Http\Controllers\Api\V1\TalentPoolController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\JobRequirement;
use App\Models\Pegawai;
use App\Models\ScreeningTalent;
use App\Models\TalentPool;
use Illuminate\Support\Facades\Route;

// Semua route otomatis berprefix /api/v1.
Route::get('/health', HealthController::class)->name('health');

// Auth publik.
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/register/talent', [AuthController::class, 'registerTalent'])
        ->name('auth.register.talent');

    Route::post('/register/hr-client', [AuthController::class, 'registerHrClient'])
        ->name('auth.register.hr-client');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('auth.login');
});

// Semua route berikut memerlukan autentikasi.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');

    Route::get('/auth/me', [AuthController::class, 'me'])
        ->name('auth.me');

    // Pengaturan akun: ubah password sendiri (semua role).
    Route::put('/auth/password', [AuthController::class, 'ubahPassword'])
        ->middleware('throttle:10,1')
        ->name('auth.password');

    // FR-03, FR-04: profil, kompetensi, dan generate CV — khusus Talent (data milik sendiri).
    Route::prefix('talent')->middleware('role:talent')->group(function () {
        Route::get('/profil', [TalentController::class, 'showProfile'])
            ->name('talent.profil.show');

        Route::put('/profil', [TalentController::class, 'updateProfile'])
            ->name('talent.profil.update');

        Route::put('/profil/kompetensi', [TalentController::class, 'syncKompetensi'])
            ->name('talent.profil.kompetensi');

        Route::post('/cv/generate', [TalentCvController::class, 'generate'])
            ->name('talent.cv.generate');

        // FR-06: unduh CV milik sendiri.
        Route::get('/cv/download', [TalentCvController::class, 'download'])
            ->name('talent.cv.download');
    });

    // FR-04: master kompetensi — dibaca semua role, dikelola TS & Manager.
    Route::get('/kompetensi', [KompetensiController::class, 'index'])
        ->name('kompetensi.index');

    Route::middleware('role:talent_support,manager')->group(function () {
        Route::post('/kompetensi', [KompetensiController::class, 'store'])
            ->name('kompetensi.store');

        Route::put('/kompetensi/{kompetensi}', [KompetensiController::class, 'update'])
            ->name('kompetensi.update');

        Route::delete('/kompetensi/{kompetensi}', [KompetensiController::class, 'destroy'])
            ->can('delete', 'kompetensi')
            ->name('kompetensi.destroy');
    });

    // FR-06: talent pool — dilihat internal (AM, TS, Manager), diubah khusus TS.
    Route::prefix('talent-pool')->middleware('role:account_manager,talent_support,manager')->group(function () {
        Route::get('/', [TalentPoolController::class, 'index'])
            ->can('viewAny', TalentPool::class)
            ->name('talent-pool.index');

        Route::get('/{talentPool}', [TalentPoolController::class, 'show'])
            ->can('view', 'talentPool')
            ->name('talent-pool.show');

        Route::get('/{talentPool}/cv', [TalentPoolController::class, 'downloadCv'])
            ->can('view', 'talentPool')
            ->name('talent-pool.cv');

        Route::patch('/{talentPool}', [TalentPoolController::class, 'update'])
            ->middleware('role:talent_support')
            ->name('talent-pool.update');
    });

    // FR-05, FR-07, FR-09: job requirement, rekomendasi kandidat, dan log progres.
    // HR Client hanya job milik kliennya (Policy + scoping di service); internal semua.
    Route::prefix('job-requirements')->middleware('role:hr_client,account_manager,talent_support,manager')->group(function () {
        Route::get('/', [JobRequirementController::class, 'index'])
            ->can('viewAny', JobRequirement::class)
            ->name('job-requirements.index');

        Route::post('/', [JobRequirementController::class, 'store'])
            ->middleware('role:hr_client,account_manager')
            ->name('job-requirements.store');

        Route::get('/{jobRequirement}', [JobRequirementController::class, 'show'])
            ->can('view', 'jobRequirement')
            ->name('job-requirements.show');

        Route::put('/{jobRequirement}', [JobRequirementController::class, 'update'])
            ->middleware('role:hr_client,account_manager')
            ->name('job-requirements.update');

        Route::patch('/{jobRequirement}/status', [JobRequirementController::class, 'updateStatus'])
            ->middleware('role:hr_client,account_manager')
            ->name('job-requirements.status');

        Route::get('/{jobRequirement}/rekomendasi', [JobRequirementController::class, 'rekomendasi'])
            ->middleware('role:talent_support')
            ->can('rekomendasi', 'jobRequirement')
            ->name('job-requirements.rekomendasi');

        Route::get('/{jobRequirement}/progres', [JobRequirementController::class, 'progres'])
            ->can('view', 'jobRequirement')
            ->name('job-requirements.progres');
    });

    // FR-09: tabel monitoring progres semua job — khusus internal.
    Route::get('/progres', [ProgresController::class, 'index'])
        ->middleware('role:account_manager,talent_support,manager')
        ->name('progres.index');

    // FR-08: screening — dilihat internal, dibuat & diubah khusus TS.
    Route::prefix('screening')->middleware('role:account_manager,talent_support,manager')->group(function () {
        Route::get('/', [ScreeningController::class, 'index'])
            ->can('viewAny', ScreeningTalent::class)
            ->name('screening.index');

        Route::post('/', [ScreeningController::class, 'store'])
            ->middleware('role:talent_support')
            ->name('screening.store');

        Route::put('/{screening}', [ScreeningController::class, 'update'])
            ->middleware('role:talent_support')
            ->name('screening.update');
    });

    // FR-10, FR-11, FR-12, FR-17: pengajuan kandidat, interview, evaluasi, dan placement.
    // Talent: data miliknya; HR Client: data kliennya (Policy + scoping di service); internal: semua.
    Route::prefix('pengajuan')->group(function () {
        Route::get('/', [PengajuanController::class, 'index'])
            ->name('pengajuan.index');

        Route::post('/', [PengajuanController::class, 'store'])
            ->middleware('role:account_manager')
            ->name('pengajuan.store');

        Route::get('/{pengajuan}', [PengajuanController::class, 'show'])
            ->can('view', 'pengajuan')
            ->name('pengajuan.show');

        Route::patch('/{pengajuan}/status', [PengajuanController::class, 'updateStatus'])
            ->middleware('role:hr_client')
            ->name('pengajuan.status');
    });

    Route::prefix('interview')->group(function () {
        Route::get('/', [InterviewController::class, 'index'])
            ->name('interview.index');

        Route::post('/', [InterviewController::class, 'store'])
            ->middleware('role:account_manager,hr_client')
            ->name('interview.store');

        Route::put('/{interview}', [InterviewController::class, 'update'])
            ->middleware('role:account_manager,hr_client')
            ->name('interview.update');
    });

    Route::post('/evaluasi', [EvaluasiController::class, 'store'])
        ->middleware('role:hr_client')
        ->name('evaluasi.store');

    Route::get('/evaluasi/{evaluasi}', [EvaluasiController::class, 'show'])
        ->middleware('role:hr_client,account_manager,talent_support,manager')
        ->can('view', 'evaluasi')
        ->name('evaluasi.show');

    Route::prefix('placement')->group(function () {
        Route::get('/', [PlacementController::class, 'index'])
            ->name('placement.index');

        Route::post('/', [PlacementController::class, 'store'])
            ->middleware('role:talent_support')
            ->name('placement.store');

        Route::get('/{placement}', [PlacementController::class, 'show'])
            ->can('view', 'placement')
            ->name('placement.show');

        Route::patch('/{placement}/status', [PlacementController::class, 'updateStatus'])
            ->middleware('role:talent_support,manager')
            ->name('placement.status');
    });

    // FR-14, FR-15, FR-18, FR-19: operasional talent yang sudah ditempatkan (cuti, shift, monitoring, keluhan).
    // Talent: data miliknya; HR Client: placement di kliennya (scoping di service + Policy); internal sesuai role.
    Route::prefix('cuti')->group(function () {
        Route::get('/', [CutiController::class, 'index'])
            ->middleware('role:talent,hr_client,manager')
            ->name('cuti.index');

        Route::post('/', [CutiController::class, 'store'])
            ->middleware('role:talent')
            ->name('cuti.store');

        Route::patch('/{cuti}/approval', [CutiController::class, 'approval'])
            ->middleware('role:hr_client,manager')
            ->name('cuti.approval');

        Route::patch('/{cuti}/batal', [CutiController::class, 'batal'])
            ->middleware('role:talent')
            ->can('batal', 'cuti')
            ->name('cuti.batal');
    });

    Route::prefix('shift')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])
            ->middleware('role:talent,hr_client,manager')
            ->name('shift.index');

        Route::middleware('role:hr_client,manager')->group(function () {
            Route::post('/', [ShiftController::class, 'store'])
                ->name('shift.store');

            Route::put('/{shift}', [ShiftController::class, 'update'])
                ->name('shift.update');

            Route::delete('/{shift}', [ShiftController::class, 'destroy'])
                ->name('shift.destroy');
        });
    });

    Route::get('/monitoring', [MonitoringController::class, 'index'])
        ->middleware('role:manager,account_manager,talent')
        ->name('monitoring.index');

    Route::post('/monitoring', [MonitoringController::class, 'store'])
        ->middleware('role:manager')
        ->name('monitoring.store');

    Route::prefix('laporan-keluhan')->group(function () {
        Route::get('/', [LaporanKeluhanController::class, 'index'])
            ->middleware('role:hr_client,account_manager,manager')
            ->name('laporan-keluhan.index');

        Route::post('/', [LaporanKeluhanController::class, 'store'])
            ->middleware('role:hr_client')
            ->name('laporan-keluhan.store');

        Route::patch('/{laporanKeluhan}/tindak-lanjut', [LaporanKeluhanController::class, 'tindakLanjut'])
            ->middleware('role:account_manager,manager')
            ->name('laporan-keluhan.tindak-lanjut');
    });

    // FR-13: dokumen & tanda tangan digital (R25–R28). Scope per data lewat DokumenPolicy.
    Route::prefix('dokumen')->group(function () {
        Route::get('/', [DokumenController::class, 'index'])
            ->name('dokumen.index');

        Route::post('/upload', [DokumenController::class, 'upload'])
            ->middleware('role:account_manager,hr_client,manager,talent')
            ->name('dokumen.upload');

        Route::post('/generate', [DokumenController::class, 'generate'])
            ->middleware('role:account_manager')
            ->name('dokumen.generate');

        Route::get('/{dokumen}/download', [DokumenController::class, 'download'])
            ->can('view', 'dokumen')
            ->name('dokumen.download');

        Route::get('/{dokumen}/calon-penandatangan', [DokumenController::class, 'calonPenandatangan'])
            ->can('aturPenandatangan', 'dokumen')
            ->name('dokumen.calon-penandatangan');

        Route::post('/{dokumen}/penandatangan', [DokumenController::class, 'penandatangan'])
            ->name('dokumen.penandatangan');

        Route::post('/{dokumen}/tanda-tangan', [DokumenController::class, 'tandaTangan'])
            ->name('dokumen.tanda-tangan');
    });

    // FR-20: dashboard — agregasi Manager (R32) dan ringkasan beranda per role.
    Route::get('/dashboard/manager', [DashboardController::class, 'manager'])
        ->middleware('role:manager')
        ->name('dashboard.manager');

    Route::get('/dashboard/ringkasan', [DashboardController::class, 'ringkasan'])
        ->name('dashboard.ringkasan');

    // FR-16: notifikasi milik user yang login (semua role).
    Route::prefix('notifikasi')->group(function () {
        Route::get('/', [NotifikasiController::class, 'index'])
            ->name('notifikasi.index');

        Route::get('/belum-dibaca/jumlah', [NotifikasiController::class, 'jumlahBelumDibaca'])
            ->name('notifikasi.jumlah');

        Route::patch('/baca-semua', [NotifikasiController::class, 'bacaSemua'])
            ->name('notifikasi.baca-semua');

        Route::patch('/{notifikasi}/baca', [NotifikasiController::class, 'baca'])
            ->can('update', 'notifikasi')
            ->name('notifikasi.baca');
    });

    // Daftar klien (read-only) untuk pilihan klien job requirement — khusus internal.
    Route::get('/klien', [KlienController::class, 'index'])
        ->middleware('role:account_manager,talent_support,manager')
        ->name('klien.index');

    // Kelola akun internal — khusus Manager.
    Route::middleware('role:manager')->group(function () {
        Route::get('/pegawai', [PegawaiController::class, 'index'])
            ->can('viewAny', Pegawai::class)
            ->name('pegawai.index');

        Route::post('/pegawai', [PegawaiController::class, 'store'])
            ->name('pegawai.store');

        Route::get('/pegawai/{pegawai}', [PegawaiController::class, 'show'])
            ->can('view', 'pegawai')
            ->name('pegawai.show');

        Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])
            ->name('pegawai.update');

        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.status');
    });
});
