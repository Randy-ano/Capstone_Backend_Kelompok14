<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterHrClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'nama_hr' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => ['example' => 'hr@majujaya.co.id'],
            'password' => ['example' => 'rahasia123'],
            'nama_hr' => ['example' => 'Sari Wulandari'],
            'jabatan' => ['example' => 'HR Manager'],
            'nama_perusahaan' => ['description' => 'Perusahaan klien baru yang dibuat bersama akun ini.', 'example' => 'PT Maju Jaya'],
            'alamat' => ['example' => 'Jl. Jend. Sudirman No. 21, Jakarta Selatan'],
            'no_telepon' => ['example' => '021-5550101'],
            'penanggung_jawab' => ['example' => 'Rudi Hartanto'],
        ];
    }
}
