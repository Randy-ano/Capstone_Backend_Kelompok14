<?php

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

class IndexMonitoringRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['id_placement' => 'placement'];
    }

    public function queryParameters(): array
    {
        return [
            'id_placement' => ['example' => 1],
            'search' => ['description' => 'Cari nama talent, posisi, atau klien.', 'example' => 'budi'],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
