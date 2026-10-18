<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PegawaiController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Route;

// Semua route di file ini otomatis berprefix /api/v1 (lihat bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');

// Auth publik (dibatasi 10 percobaan per menit per IP).
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/register/talent', [AuthController::class, 'registerTalent'])->name('auth.register.talent');
    Route::post('/register/hr-client', [AuthController::class, 'registerHrClient'])->name('auth.register.hr-client');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // Kelola akun internal (R2) — khusus Manager.
    Route::middleware('role:manager')->group(function () {
        Route::get('/pegawai', [PegawaiController::class, 'index'])->can('viewAny', Pegawai::class)->name('pegawai.index');
        Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
        Route::get('/pegawai/{pegawai}', [PegawaiController::class, 'show'])->can('view', 'pegawai')->name('pegawai.show');
        Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->name('pegawai.update');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    });
});
