<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Models\Klien;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class AuthService
{
    private const NAMA_TOKEN = 'api';

    /**
     * R1: registrasi talent → users + talent (belum_lengkap).
     *
     * @param  array{email: string, password: string, nama_lengkap: string}  $data
     * @return array{token: string, user: User}
     */
    public function registerTalent(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = $this->buatUser($data['email'], $data['password'], Role::Talent);
            $user->talent()->create([
                'nama_lengkap' => $data['nama_lengkap'],
                'status_kelengkapan' => StatusKelengkapan::BelumLengkap,
            ]);

            return $user;
        });

        return $this->sesiBaru($user);
    }

    /**
     * R1: registrasi HR Client → klien baru + users + hr_client.
     *
     * @param  array<string, mixed>  $data
     * @return array{token: string, user: User}
     */
    public function registerHrClient(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $klien = Klien::create([
                'nama_perusahaan' => $data['nama_perusahaan'],
                'alamat' => $data['alamat'] ?? null,
                'no_telepon' => $data['no_telepon'] ?? null,
                'penanggung_jawab' => $data['penanggung_jawab'] ?? null,
            ]);

            $user = $this->buatUser($data['email'], $data['password'], Role::HrClient);
            $user->hrClient()->create([
                'id_klien' => $klien->id_klien,
                'nama_hr' => $data['nama_hr'],
                'jabatan' => $data['jabatan'] ?? null,
            ]);

            return $user;
        });

        return $this->sesiBaru($user);
    }

    /**
     * Login dengan email & password. R3: akun nonaktif ditolak (403).
     *
     * @return array{token: string, user: User}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! $user->status_aktif) {
            throw new AccessDeniedHttpException('Akun Anda dinonaktifkan. Silakan hubungi Manager PT Intellisys.');
        }

        return $this->sesiBaru($user);
    }

    /**
     * Hapus token yang sedang dipakai.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Ubah password sendiri. Sesi lain (token di perangkat lain) dihapus; token yang sedang dipakai tetap berlaku.
     */
    public function ubahPassword(User $user, string $passwordBaru): void
    {
        $user->update(['password' => $passwordBaru]);

        $token = $user->currentAccessToken();
        $user->tokens()
            ->when($token instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($token->getKey()))
            ->delete();
    }

    /**
     * Kirim tautan reset password lewat email (token berlaku 60 menit). Hasilnya sengaja diabaikan:
     * email tidak terdaftar atau permintaan berulang terlalu cepat menghasilkan respons yang sama (tidak membocorkan akun).
     * Pengiriman gagal (mis. API key Brevo salah atau kuota habis) hanya dicatat di log; respons ke pengguna tetap sama.
     */
    public function lupaPassword(string $email): void
    {
        try {
            Password::sendResetLink(['email' => $email]);
        } catch (Throwable $e) {
            Log::error('Gagal mengirim email reset password.', [
                'email' => $email,
                'mailer' => config('mail.default'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reset password dengan token dari email. Berhasil → semua token login lama dicabut.
     * Token salah, kedaluwarsa, atau email tidak cocok → 422 dengan pesan yang sama.
     */
    public function resetPassword(string $email, string $token, string $passwordBaru): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $passwordBaru],
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
        }
    }

    private function buatUser(string $email, string $password, Role $role): User
    {
        return User::create([
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);
    }

    /**
     * @return array{token: string, user: User}
     */
    private function sesiBaru(User $user): array
    {
        return [
            'token' => $user->createToken(self::NAMA_TOKEN)->plainTextToken,
            'user' => $user,
        ];
    }
}
