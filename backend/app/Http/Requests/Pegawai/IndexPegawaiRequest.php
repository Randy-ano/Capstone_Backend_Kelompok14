<?php

namespace App\Http\Requests\Pegawai;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPegawaiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(array_map(fn (Role $role) => $role->value, Role::internal()))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'Cari berdasarkan nama atau email.', 'example' => 'rina'],
            'role' => ['description' => 'Filter role: account_manager, talent_support, manager.', 'example' => 'talent_support'],
            'per_page' => ['description' => 'Jumlah data per halaman (1–100, default 15).', 'example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
