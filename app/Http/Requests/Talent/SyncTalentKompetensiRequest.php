<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class SyncTalentKompetensiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Boleh kosong ([]) untuk menghapus semua kompetensi; status kelengkapan ikut turun (R4).
            'id_kompetensi' => [
                'present',
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
            'id_kompetensi.present' =>
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

    public function bodyParameters(): array
    {
        return [
            'id_kompetensi' => ['description' => 'Daftar lengkap id kompetensi talent (menggantikan daftar lama).', 'example' => [1, 4, 7]],
            'id_kompetensi.*' => ['example' => 1],
        ];
    }
}
