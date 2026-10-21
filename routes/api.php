<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\JobRequirementController;
use App\Http\Controllers\Api\V1\KlienController;
use App\Http\Controllers\Api\V1\KompetensiController;
use App\Http\Controllers\Api\V1\PegawaiController;
use App\Http\Controllers\Api\V1\ProgresController;
use App\Http\Controllers\Api\V1\ScreeningController;
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
