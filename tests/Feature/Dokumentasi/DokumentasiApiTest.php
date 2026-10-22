<?php

use App\Support\DokumentasiApi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

function routeUntuk(string $method, string $uri): Illuminate\Routing\Route
{
    return Route::getRoutes()->match(Request::create($uri, $method));
}

it('memilih akun demo yang lolos semua middleware role route', function (string $method, string $uri, string $role) {
    expect(DokumentasiApi::roleUntuk(routeUntuk($method, $uri)))->toBe($role);
})->with([
    'tanpa batasan role' => ['GET', '/api/v1/auth/me', 'manager'],
    'khusus talent' => ['GET', '/api/v1/talent/profil', 'talent'],
    'khusus account manager' => ['POST', '/api/v1/dokumen/generate', 'account_manager'],
    'khusus talent support & manager' => ['GET', '/api/v1/talent-pool', 'manager'],
]);

it('memasang token akun demo hanya untuk route yang butuh login', function () {
    $this->seed(DatabaseSeeder::class);

    $publik = Request::create('/api/v1/health');
    DokumentasiApi::sebelumResponseCall($publik, routeUntuk('GET', '/api/v1/health'));
    expect($publik->headers->has('Authorization'))->toBeFalse();

    $request = Request::create('/api/v1/talent/profil');
    DokumentasiApi::sebelumResponseCall($request, routeUntuk('GET', '/api/v1/talent/profil'));
    $this->withHeader('Authorization', $request->headers->get('Authorization'))
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'talent@intellisys.test');
});
