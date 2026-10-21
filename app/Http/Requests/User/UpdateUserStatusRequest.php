<?php

namespace App\Http\Requests\User;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('updateStatus', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'status_aktif' => ['required', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status_aktif' => ['description' => 'true = aktifkan, false = nonaktifkan (semua token akun dicabut).', 'example' => false],
        ];
    }
}
