<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterTalentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['example' => 'budi@example.com'],
            'password' => ['example' => 'rahasia123'],
            'nama_lengkap' => ['example' => 'Budi Santoso'],
        ];
    }
}
