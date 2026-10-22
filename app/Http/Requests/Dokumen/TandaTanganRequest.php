<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\StatusTtd;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TandaTanganRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('tandaTangan', $this->route('dokumen'));
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in([StatusTtd::Ditandatangani->value, StatusTtd::Ditolak->value])],
            'file_tanda_tangan' => ['exclude_unless:keputusan,ditandatangani', 'required', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_tanda_tangan.required' => 'Gambar tanda tangan wajib diunggah untuk menandatangani.',
            'file_tanda_tangan.mimes' => 'Gambar tanda tangan harus berformat PNG atau JPG.',
            'file_tanda_tangan.max' => 'Ukuran gambar tanda tangan maksimal 2 MB.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'keputusan' => ['description' => 'ditandatangani atau ditolak.', 'example' => 'ditandatangani'],
            'file_tanda_tangan' => ['description' => 'Gambar tanda tangan (png/jpg, maks 2 MB); wajib jika ditandatangani.'],
        ];
    }
}
