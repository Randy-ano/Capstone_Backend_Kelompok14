<?php

namespace App\Providers;

use App\Support\DokumentasiApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Scribe;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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

        // Email lewat Brevo HTTP API (MAIL_MAILER=brevo). Railway Trial/Hobby memblokir SMTP keluar, jadi produksi
        // mengirim lewat HTTPS. API key dibaca dari config services.brevo.key (BREVO_API_KEY).
        Mail::extend('brevo', fn () => (new BrevoTransportFactory)->create(
            new Dsn('brevo+api', 'default', (string) config('services.brevo.key'))
        ));

        // Fase 10: contoh respons dokumentasi API memakai akun demo yang berhak (Scribe = dependensi dev).
        if (class_exists(Scribe::class)) {
            Scribe::beforeResponseCall(
                fn ($request, $endpoint) => DokumentasiApi::sebelumResponseCall($request, $endpoint->route)
            );
        }
    }
}
