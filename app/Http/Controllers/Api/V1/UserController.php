<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

/**
 * @group Auth & Akun
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    /**
     * Aktif/nonaktifkan akun.
     *
     * Khusus Manager, berlaku untuk akun role apa pun kecuali akunnya sendiri.
     * Akun yang dinonaktifkan langsung kehilangan semua tokennya.
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): UserResource
    {
        $aktif = $request->boolean('status_aktif');
        $user = $this->users->ubahStatus($user, $aktif);

        return (new UserResource($user))->additional([
            'message' => $aktif ? 'Akun berhasil diaktifkan' : 'Akun berhasil dinonaktifkan',
        ]);
    }
}
