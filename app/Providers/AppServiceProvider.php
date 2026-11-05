<?php

namespace App\Providers;

use App\Support\DokumentasiApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Scribe;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lupa & reset password: 5 permintaan per menit per IP, terpisah dari batas login/registrasi.
        RateLimiter::for('reset-password', fn (Request $request) => Limit::perMinute(5)->by('reset-password|'.$request->ip()));

        // Fase 10: contoh respons dokumentasi API memakai akun demo yang berhak (Scribe = dependensi dev).
        if (class_exists(Scribe::class)) {
            Scribe::beforeResponseCall(
                fn ($request, $endpoint) => DokumentasiApi::sebelumResponseCall($request, $endpoint->route)
            );
        }
    }
}
