<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Ubah password sendiri (semua role). Password lama dicek terhadap user yang login.
 */
class UbahPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password_lama' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'different:password_lama'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
        ];
    }

    public function attributes(): array
    {
        return [
            'password_lama' => 'password lama',
            'password' => 'password baru',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'password_lama' => ['example' => 'password'],
            'password' => ['description' => 'Minimal 8 karakter, berbeda dari password lama.', 'example' => 'rahasiabaru123'],
        ];
    }
}
