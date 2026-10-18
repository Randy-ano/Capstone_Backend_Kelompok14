<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * R2: akun pegawai (AM/TS/Manager) hanya dibuat oleh seeder atau Manager.
 */
class PegawaiService
{
    /**
     * @param  array{search?: string|null, role?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(array $filter): LengthAwarePaginator
    {
        return Pegawai::query()
            ->with('user')
            ->when($filter['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('nama_pegawai', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"))))
            ->when($filter['role'] ?? null, fn ($q, $role) => $q->whereHas('user', fn ($q) => $q->where('role', $role)))
            ->orderBy('nama_pegawai')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @param  array{email: string, password: string, role: string, nama_pegawai: string, jabatan: string, no_telepon?: string|null}  $data
     */
    public function buat(array $data): Pegawai
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::from($data['role']),
            ]);

            return $user->pegawai()->create(Arr::only($data, ['nama_pegawai', 'jabatan', 'no_telepon']))->setRelation('user', $user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(Pegawai $pegawai, array $data): Pegawai
    {
        return DB::transaction(function () use ($pegawai, $data) {
            $pegawai->update(Arr::only($data, ['nama_pegawai', 'jabatan', 'no_telepon']));

            $akun = Arr::only($data, ['email', 'password', 'role']);
            if ($akun !== []) {
                $pegawai->user->update($akun);
            }

            return $pegawai->load('user');
        });
    }
}
