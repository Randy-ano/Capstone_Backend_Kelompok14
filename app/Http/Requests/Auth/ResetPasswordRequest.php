<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'password baru'];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'Email akun (dari tautan di email).', 'example' => 'talent@intellisys.test'],
            'token' => ['description' => 'Token dari tautan di email.', 'example' => '2f1c9a...'],
            'password' => ['description' => 'Password baru, minimal 8 karakter.', 'example' => 'rahasiabaru123'],
            'password_confirmation' => ['description' => 'Harus sama dengan password baru.', 'example' => 'rahasiabaru123'],
        ];
    }
}
