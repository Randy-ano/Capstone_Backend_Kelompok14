<?php

namespace App\Http\Requests\Pegawai;

use App\Enums\Role;
use App\Models\Pegawai;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Pegawai::class);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::in(array_map(fn (Role $role) => $role->value, Role::internal()))],
            'nama_pegawai' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['example' => 'dimas@intellisys.test'],
            'password' => ['example' => 'rahasia123'],
            'role' => ['description' => 'Salah satu: account_manager, talent_support, manager.', 'example' => 'talent_support'],
            'nama_pegawai' => ['example' => 'Dimas Prasetyo'],
            'jabatan' => ['example' => 'Talent Support'],
            'no_telepon' => ['example' => '0812-3456-7890'],
        ];
    }
}
