<?php

it('mengembalikan status ok', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

it('tidak tersedia tanpa prefix v1', function () {
    $this->getJson('/api/health')->assertNotFound();
});

it('mengizinkan CORS dari origin frontend', function () {
    $this->withHeaders([
        'Origin' => 'http://localhost:5173',
        'Access-Control-Request-Method' => 'GET',
    ])->options('/api/v1/health')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

it('tidak mengizinkan CORS dari origin lain', function () {
    $response = $this->withHeaders([
        'Origin' => 'http://evil.test',
        'Access-Control-Request-Method' => 'GET',
    ])->options('/api/v1/health');

    // Dengan satu origin terdaftar, header selalu berisi origin itu sehingga browser menolak origin lain.
    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('http://evil.test');
});

it('memakai zona waktu dan locale yang benar', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(config('app.locale'))->toBe('id');
});
