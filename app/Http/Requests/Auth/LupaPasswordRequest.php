<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LupaPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'Email akun.', 'example' => 'talent@intellisys.test'],
        ];
    }
}
