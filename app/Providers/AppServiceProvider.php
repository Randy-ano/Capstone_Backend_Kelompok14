<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Fase 10: contoh respons dokumentasi API memakai akun demo yang berhak (Scribe = dependensi dev).
        if (class_exists(\Knuckles\Scribe\Scribe::class)) {
            \Knuckles\Scribe\Scribe::beforeResponseCall(
                fn ($request, $endpoint) => \App\Support\DokumentasiApi::sebelumResponseCall($request, $endpoint->route)
            );
        }
    }
}
