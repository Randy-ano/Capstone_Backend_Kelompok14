<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PegawaiController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Controllers\Api\V1\TalentCvController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\Pegawai;
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
    });

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
