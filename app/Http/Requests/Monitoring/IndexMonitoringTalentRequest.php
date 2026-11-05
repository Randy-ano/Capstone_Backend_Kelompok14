<?php

namespace App\Http\Requests\Monitoring;

use App\Enums\StatusTampilPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMonitoringTalentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusTampilPlacement::class)],
            'id_klien' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status kontrak', 'id_klien' => 'klien'];
    }

    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'Status kontrak: belum_aktif, aktif, selesai.', 'example' => 'aktif'],
            'id_klien' => ['description' => 'Khusus internal.', 'example' => 1],
            'search' => ['description' => 'Cari nama talent atau posisi.', 'example' => 'budi'],
            'per_page' => ['example' => 12],
            'page' => ['example' => 1],
        ];
    }
}
