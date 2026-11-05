<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * File sertifikat: PDF/JPG/PNG, maks 2 MB.
 */
class UnggahFileSertifikatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file sertifikat yang akan diunggah.',
            'file.mimes' => 'File sertifikat harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran file sertifikat maksimal 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'file sertifikat'];
    }

    public function bodyParameters(): array
    {
        return [
            'file' => ['description' => 'File sertifikat (pdf/jpg/png, maks 2 MB).'],
        ];
    }
}
