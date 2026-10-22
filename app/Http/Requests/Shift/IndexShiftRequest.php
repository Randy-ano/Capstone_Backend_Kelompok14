<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class IndexShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['nullable', 'integer'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['id_placement' => 'placement', 'dari' => 'tanggal awal', 'sampai' => 'tanggal akhir'];
    }

    public function queryParameters(): array
    {
        return [
            'id_placement' => ['example' => 1],
            'dari' => ['description' => 'Tanggal awal (Y-m-d).', 'example' => '2026-11-01'],
            'sampai' => ['description' => 'Tanggal akhir (Y-m-d).', 'example' => '2026-11-30'],
            'per_page' => ['description' => 'Default 31.', 'example' => 31],
            'page' => ['example' => 1],
        ];
    }
}
