<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterHrClientRequest;
use App\Http\Requests\Auth\RegisterTalentRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Auth & Akun
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /**
     * Registrasi talent.
     *
     * Membuat akun talent dengan profil berstatus `belum_lengkap`, lalu langsung login.
     *
     * @unauthenticated
     *
     * @bodyParam password_confirmation string required Harus sama dengan password. Example: rahasia123
     */
    public function registerTalent(RegisterTalentRequest $request): JsonResponse
    {
        $sesi = $this->auth->registerTalent($request->validated());

        return $this->sesiResponse($sesi, 'Registrasi berhasil', 201);
    }

    /**
     * Registrasi HR Client.
     *
     * Membuat perusahaan klien baru beserta akun HR-nya, lalu langsung login.
     *
     * @unauthenticated
     *
     * @bodyParam password_confirmation string required Harus sama dengan password. Example: rahasia123
     */
    public function registerHrClient(RegisterHrClientRequest $request): JsonResponse
    {
        $sesi = $this->auth->registerHrClient($request->validated());

        return $this->sesiResponse($sesi, 'Registrasi berhasil', 201);
    }

    /**
     * Login.
     *
     * Mengembalikan token Bearer, data user, role, dan profil. Akun nonaktif ditolak (403).
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $sesi = $this->auth->login($request->validated('email'), $request->validated('password'));

        return $this->sesiResponse($sesi, 'Login berhasil');
    }

    /**
     * Logout.
     *
     * Menghapus token yang sedang dipakai.
     *
     * @response {"data": null, "message": "Logout berhasil"}
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['data' => null, 'message' => 'Logout berhasil']);
    }

    /**
     * Data user yang sedang login.
     *
     * Berisi user beserta profil sesuai role.
     */
    public function me(Request $request): UserResource
    {
        return (new UserResource($request->user()))->additional(['message' => 'Berhasil']);
    }

    /**
     * @param  array{token: string, user: User}  $sesi
     */
    private function sesiResponse(array $sesi, string $pesan, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $sesi['token'],
                'user' => new UserResource($sesi['user']),
            ],
            'message' => $pesan,
        ], $status);
    }
}
