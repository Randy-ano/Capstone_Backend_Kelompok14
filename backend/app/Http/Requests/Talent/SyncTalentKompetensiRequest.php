<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class SyncTalentKompetensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->talent !== null;
    }

    public function rules(): array
    {
        return [
            'id_kompetensi' => [
                'required',
                'array',
            ],
            'id_kompetensi.*' => [
                'required',
                'integer',
                'distinct',
                'exists:kompetensi,id_kompetensi',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kompetensi.required' =>
                'Daftar kompetensi wajib dikirim.',
            'id_kompetensi.array' =>
                'Daftar kompetensi harus berupa array.',
            'id_kompetensi.*.required' =>
                'ID kompetensi tidak boleh kosong.',
            'id_kompetensi.*.integer' =>
                'ID kompetensi harus berupa angka.',
            'id_kompetensi.*.distinct' =>
                'Kompetensi tidak boleh dipilih berulang kali.',
            'id_kompetensi.*.exists' =>
                'Kompetensi yang dipilih tidak ditemukan.',
        ];
    }
}
