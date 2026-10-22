<?php

namespace App\Http\Requests\Cuti;

use App\Enums\StatusCuti;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCutiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusCuti::class)],
            'id_placement' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status cuti', 'id_placement' => 'placement'];
    }

    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'menunggu, disetujui, ditolak, atau dibatalkan.', 'example' => 'menunggu'],
            'id_placement' => ['example' => 1],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
