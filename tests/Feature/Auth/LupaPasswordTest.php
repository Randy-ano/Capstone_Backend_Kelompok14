<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

const PESAN_LUPA = 'Jika email terdaftar, tautan reset sudah dikirim.';
const PESAN_TOKEN_SALAH = 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.';

describe('POST /auth/lupa-password', function () {
    it('mengirim email berisi tautan ke halaman reset di frontend', function () {
        Notification::fake();
        $user = User::factory()->create(['email' => 'budi@contoh.com', 'role' => Role::Talent]);

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'budi@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $n) use ($user) {
            $url = $n->url($user);
            $mail = $n->toMail($user);

            return str_starts_with($url, 'http://localhost:5173/reset-password?token=')
                && str_contains($url, 'email=budi%40contoh.com')
                && Password::broker()->tokenExists($user, $n->token)
                && $mail->subject === '[Intellisys Talent Hub] Reset password'
                && $mail->viewData['url'] === $url
                && $mail->viewData['menit'] === 60;
        });
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'budi@contoh.com']);
    });

    it('isi email berbahasa Indonesia dan memuat tautan', function () {
        $user = User::factory()->create(['email' => 'sari@contoh.com', 'role' => Role::HrClient]);
        $html = (string) (new ResetPasswordNotification('token-uji'))->toMail($user)->render();

        expect($html)->toContain('Kami menerima permintaan untuk mengatur ulang password')
            ->toContain('http://localhost:5173/reset-password?token=token-uji&email=sari%40contoh.com')
            ->not->toContain('&amp;')
            ->toContain('berlaku selama 60 menit');
    });

    it('respons sama untuk email yang tidak terdaftar dan tidak mengirim apa pun', function () {
        Notification::fake();
        Mail::fake();

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'tidakada@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA]);

        Notification::assertNothingSent();
        Mail::assertNothingSent();
    });

    it('permintaan ulang dalam 60 detik tidak mengirim email baru, respons tetap sama', function () {
        Notification::fake();
        $user = User::factory()->create(['email' => 'budi@contoh.com']);

        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'budi@contoh.com'])->assertOk();
        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'budi@contoh.com'])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => PESAN_LUPA]);

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    });

    it('memvalidasi email (422)', function (array $body) {
        $this->postJson('/api/v1/auth/lupa-password', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    })->with([
        'kosong' => [[]],
        'format salah' => [['email' => 'bukan-email']],
    ]);

    it('dibatasi 5 permintaan per menit (429)', function () {
        Notification::fake();

        foreach (range(1, 5) as $_) {
            $this->postJson('/api/v1/auth/lupa-password', ['email' => 'siapa@contoh.com'])->assertOk();
        }
        $this->postJson('/api/v1/auth/lupa-password', ['email' => 'siapa@contoh.com'])->assertTooManyRequests();
        $this->postJson('/api/v1/auth/reset-password', [])->assertTooManyRequests();

        // Batas ini terpisah dari batas login.
        User::factory()->create(['email' => 'budi@contoh.com']);
        $this->postJson('/api/v1/auth/login', ['email' => 'budi@contoh.com', 'password' => 'password'])->assertOk();
    });
});

describe('POST /auth/reset-password', function () {
    it('mengganti password, mencabut semua token login lama, lalu bisa login dengan password baru', function () {
        $user = User::factory()->create(['email' => 'budi@contoh.com', 'role' => Role::Talent]);
        $sesiLama = $user->createToken('api')->plainTextToken;
        $user->createToken('api');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'budi@contoh.com',
            'token' => $token,
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => 'Password berhasil direset. Silakan masuk dengan password baru.']);

        expect(Hash::check('rahasiabaru123', $user->fresh()->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(0);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'budi@contoh.com']);

        app('auth')->forgetGuards();
        $this->withToken($sesiLama)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['email' => 'budi@contoh.com', 'password' => 'rahasiabaru123'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'budi@contoh.com', 'password' => 'password'])->assertUnprocessable();
    });

    it('token hanya dapat dipakai sekali', function () {
        $user = User::factory()->create(['email' => 'budi@contoh.com']);
        $body = ['email' => 'budi@contoh.com', 'token' => Password::broker()->createToken($user), 'password' => 'rahasiabaru123', 'password_confirmation' => 'rahasiabaru123'];

        $this->postJson('/api/v1/auth/reset-password', $body)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $body)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', PESAN_TOKEN_SALAH);
    });

    it('menolak token salah, kedaluwarsa (> 60 menit), atau email lain dengan pesan yang sama', function (string $kasus) {
        $user = User::factory()->create(['email' => 'budi@contoh.com']);
        User::factory()->create(['email' => 'sari@contoh.com']);
        $token = Password::broker()->createToken($user);
        $body = ['email' => 'budi@contoh.com', 'token' => $token, 'password' => 'rahasiabaru123', 'password_confirmation' => 'rahasiabaru123'];

        match ($kasus) {
            'salah' => $body['token'] = 'token-salah',
            'kedaluwarsa' => $this->travel(61)->minutes(),
            'email lain' => $body['email'] = 'sari@contoh.com',
            'tidak terdaftar' => $body['email'] = 'tidakada@contoh.com',
        };

        $this->postJson('/api/v1/auth/reset-password', $body)
            ->assertUnprocessable()
            ->assertJsonPath('message', PESAN_TOKEN_SALAH)
            ->assertJsonPath('errors.token.0', PESAN_TOKEN_SALAH);
        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    })->with(['salah', 'kedaluwarsa', 'email lain', 'tidak terdaftar']);

    it('memvalidasi isian (422)', function (array $ubah, string $field) {
        $body = ['email' => 'budi@contoh.com', 'token' => 'abc', 'password' => 'rahasiabaru123', 'password_confirmation' => 'rahasiabaru123', ...$ubah];

        $this->postJson('/api/v1/auth/reset-password', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    })->with([
        'email kosong' => [['email' => ''], 'email'],
        'token kosong' => [['token' => ''], 'token'],
        'password pendek' => [['password' => 'abc', 'password_confirmation' => 'abc'], 'password'],
        'konfirmasi beda' => [['password_confirmation' => 'lainlagi123'], 'password'],
    ]);
});
