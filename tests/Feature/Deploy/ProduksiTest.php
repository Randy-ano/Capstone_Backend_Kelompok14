<?php

use App\Enums\KanalNotifikasi;
use App\Enums\Role;
use App\Enums\StatusPengiriman;
use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Mail;

/** Muat ulang config/cors.php dengan nilai FRONTEND_URL tertentu. */
function corsDenganFrontendUrl(?string $nilai): array
{
    $atur = function (?string $env, ?string $server) {
        unset($_ENV['FRONTEND_URL'], $_SERVER['FRONTEND_URL']);
        if ($env !== null) {
            $_ENV['FRONTEND_URL'] = $env;
        }
        if ($server !== null) {
            $_SERVER['FRONTEND_URL'] = $server;
        }
    };
    $lama = [$_ENV['FRONTEND_URL'] ?? null, $_SERVER['FRONTEND_URL'] ?? null];
    $atur($nilai, $nilai);

    try {
        return require config_path('cors.php');
    } finally {
        $atur(...$lama);
    }
}

describe('CORS dari FRONTEND_URL', function () {
    it('membaca beberapa origin dipisah koma dan selalu mengizinkan localhost:5173', function () {
        expect(corsDenganFrontendUrl(' https://intellisys.vercel.app/ , https://demo.intellisys.id')['allowed_origins'])
            ->toBe(['https://intellisys.vercel.app', 'https://demo.intellisys.id', 'http://localhost:5173']);
    });

    it('tanpa FRONTEND_URL hanya localhost:5173, tanpa duplikat', function () {
        expect(corsDenganFrontendUrl(null)['allowed_origins'])->toBe(['http://localhost:5173'])
            ->and(corsDenganFrontendUrl('http://localhost:5173')['allowed_origins'])->toBe(['http://localhost:5173']);
    });

    it('preflight dari origin terdaftar diizinkan, origin lain tidak', function () {
        config(['cors.allowed_origins' => ['https://intellisys.vercel.app', 'http://localhost:5173']]);
        $preflight = fn (string $origin) => $this->call('OPTIONS', '/api/v1/auth/login', server: [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $preflight('https://intellisys.vercel.app')->assertHeader('Access-Control-Allow-Origin', 'https://intellisys.vercel.app');
        $preflight('http://localhost:5173')->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $preflight('https://situs-lain.example')->assertHeaderMissing('Access-Control-Allow-Origin');
    });
});

it('di balik proxy HTTPS, URL yang dibuat memakai https dan host publik', function () {
    loginSebagai(Role::Manager);

    $this->getJson('/api/v1/notifikasi', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'intellisys-api.up.railway.app',
        'X-Forwarded-Port' => '443',
    ])
        ->assertOk()
        ->assertJsonPath('links.first', fn (string $url) => str_starts_with($url, 'https://intellisys-api.up.railway.app/api/v1/notifikasi'));
});

describe('QUEUE_CONNECTION=sync', function () {
    beforeEach(fn () => config(['queue.default' => 'sync']));

    it('email notifikasi langsung terkirim', function () {
        Mail::fake();
        $user = buatUser(Role::Manager);

        $notifikasi = app(NotifikasiService::class)->kirim($user, 'uji', 'Pesan uji.', KanalNotifikasi::Email);

        expect($notifikasi->fresh()->status_pengiriman)->toBe(StatusPengiriman::Terkirim);
    });

    it('email gagal tidak menggagalkan proses; notifikasi ditandai gagal dan penerima berikutnya tetap diproses', function () {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP tidak dapat dihubungi'));
        $penerima = [buatUser(Role::Manager), buatUser(Role::AccountManager)];

        app(NotifikasiService::class)->kirimKe($penerima, 'uji', 'Pesan uji.', KanalNotifikasi::Email);

        expect(Notifikasi::pluck('status_pengiriman')->all())->toBe([StatusPengiriman::Gagal, StatusPengiriman::Gagal]);
    });
});
