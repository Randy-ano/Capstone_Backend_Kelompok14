<?php

namespace App\Http\Requests\Pegawai;

use App\Enums\Role;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePegawaiRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('pegawai'));
    }

    public function rules(): array
    {
        $pegawai = $this->route('pegawai');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($pegawai?->id_user, 'id_user')],
            'password' => ['sometimes', 'required', 'string', Password::min(8)],
            'role' => ['sometimes', 'required', Rule::in(array_map(fn (Role $role) => $role->value, Role::internal()))],
            'nama_pegawai' => ['sometimes', 'required', 'string', 'max:255'],
            'jabatan' => ['sometimes', 'required', 'string', 'max:255'],
            'no_telepon' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'Opsional.', 'example' => 'dimas@intellisys.test'],
            'password' => ['description' => 'Opsional, isi jika ingin mengganti password.', 'example' => 'rahasiabaru123'],
            'role' => ['description' => 'Opsional. Salah satu: account_manager, talent_support, manager.', 'example' => 'account_manager'],
            'nama_pegawai' => ['description' => 'Opsional.', 'example' => 'Dimas Prasetyo'],
            'jabatan' => ['description' => 'Opsional.', 'example' => 'Account Manager'],
            'no_telepon' => ['description' => 'Opsional.', 'example' => '0812-3456-7890'],
        ];
    }
}
