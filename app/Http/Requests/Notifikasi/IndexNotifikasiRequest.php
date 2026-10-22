<?php

namespace App\Http\Requests\Notifikasi;

use Illuminate\Foundation\Http\FormRequest;

class IndexNotifikasiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_baca' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status_baca' => 'status baca'];
    }

    public function queryParameters(): array
    {
        return [
            'status_baca' => ['description' => '0 = belum dibaca, 1 = sudah dibaca; kosong = semua.', 'example' => 0],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
