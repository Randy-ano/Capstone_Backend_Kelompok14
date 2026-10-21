<?php

namespace App\Http\Requests\Kompetensi;

use Illuminate\Foundation\Http\FormRequest;

class IndexKompetensiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'dengan_pemakaian' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'Cari berdasarkan nama kompetensi.', 'example' => 'sql'],
            'kategori' => ['description' => 'Filter kategori (sama persis).', 'example' => 'Database'],
            'dengan_pemakaian' => ['description' => 'Jika 1, setiap item memuat jumlah_talent dan jumlah_job_requirement yang memakai kompetensi tsb.', 'example' => 1],
            'per_page' => ['description' => 'Jumlah data per halaman (1–100, default 15).', 'example' => 100],
            'page' => ['example' => 1],
        ];
    }
}
