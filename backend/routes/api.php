<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

// Semua route di file ini otomatis berprefix /api/v1 (lihat bootstrap/app.php).

Route::get('/health', HealthController::class)->name('health');
