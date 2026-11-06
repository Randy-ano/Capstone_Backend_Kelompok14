<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

const PESAN_LUPA_BREVO = 'Jika email terdaftar, tautan reset sudah dikirim.';

/** Ganti transport brevo dengan versi yang memakai HTTP client palsu (tidak ada panggilan ke API Brevo sungguhan). */
function brevoPalsu(MockResponse|Closure $respons): void
{
    config(['mail.default' => 'brevo', 'services.brevo.key' => 'kunci-uji']);
    Mail::extend('brevo', fn () => new BrevoApiTransport(config('services.brevo.key'), new MockHttpClient($respons)));
    Mail::purge('brevo');
}

describe('Transport email Brevo', function () {
    it('mailer brevo terdaftar dan memakai Brevo HTTP API dengan API key dari BREVO_API_KEY', function () {
        config(['services.brevo.key' => 'kunci-uji']);

        expect(config('mail.mailers.brevo.transport'))->toBe('brevo');

        $transport = Mail::mailer('brevo')->getSymfonyTransport();

        expect($transport)->toBeInstanceOf(BrevoApiTransport::class)
            ->and((string) $transport)->toBe('brevo+api://api.brevo.com');
    });

    it('terpilih sebagai mailer default saat MAIL_MAILER=brevo', function () {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'kunci-uji']);

        expect(Mail::mailer()->getSymfonyTransport())->toBeInstanceOf(BrevoApiTransport::class);
    });

    it('bawaan lokal & test tetap log', function () {
        expect(config('mail.default'))->not->toBe('brevo');
    });

    it('lupa password mengirim email lewat endpoint Brevo dengan header api-key', function () {
        $permintaan = [];
        brevoPalsu(function (string $method, string $url, array $options) use (&$permintaan) {
            $permintaan[] = compact('method', 'url', 'options');

            return new MockResponse('{"messageId":"<uji@brevo>"}', ['http_code' => 201]);
        });
        User::factory()->create(['email' => 'budi@contoh.com', 'role' => Role::Talent]);

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'budi@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA_BREVO]);

        expect($permintaan)->toHaveCount(1)
            ->and($permintaan[0]['method'])->toBe('POST')
            ->and($permintaan[0]['url'])->toBe('https://api.brevo.com/v3/smtp/email')
            ->and(implode("\n", $permintaan[0]['options']['headers']))->toContain('api-key: kunci-uji')
            ->and($permintaan[0]['options']['body'])->toContain('budi@contoh.com')->toContain('reset-password?token=');
    });

    it('pengiriman gagal (API key salah) → pesan umum yang sama, error dicatat di log', function () {
        brevoPalsu(new MockResponse('{"code":"unauthorized","message":"Key not found"}', ['http_code' => 401]));
        Log::spy();
        User::factory()->create(['email' => 'sari@contoh.com', 'role' => Role::HrClient]);

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'sari@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA_BREVO]);

        Log::shouldHaveReceived('error')->once()->withArgs(
            fn (string $pesan, array $konteks) => $pesan === 'Gagal mengirim email reset password.'
                && $konteks['email'] === 'sari@contoh.com'
                && $konteks['mailer'] === 'brevo'
                && str_contains($konteks['error'], 'Key not found')
        );
    });

    it('pengiriman gagal untuk email tidak terdaftar tetap tidak membocorkan apa pun', function () {
        brevoPalsu(new MockResponse('', ['http_code' => 500]));

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'tidakada@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA_BREVO]);
    });
});
