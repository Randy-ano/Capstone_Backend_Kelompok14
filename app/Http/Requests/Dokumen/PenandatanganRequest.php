<?php

namespace App\Http\Requests\Dokumen;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PenandatanganRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('aturPenandatangan', $this->route('dokumen'));
    }

    public function rules(): array
    {
        return [
            'id_user' => ['required', 'array', 'min:1', 'max:10'],
            'id_user.*' => ['required', 'integer', 'distinct', 'exists:users,id_user'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_user.required' => 'Pilih minimal 1 penandatangan.',
            'id_user.min' => 'Pilih minimal 1 penandatangan.',
            'id_user.max' => 'Maksimal 10 penandatangan.',
            'id_user.*.distinct' => 'Penandatangan tidak boleh dipilih dua kali.',
            'id_user.*.exists' => 'Penandatangan yang dipilih tidak ditemukan.',
        ];
    }

    public function bodyParameters(): array
    {
        return ['id_user' => ['description' => 'Daftar id_user penandatangan (lihat calon-penandatangan).', 'example' => [3, 4]]];
    }
}
