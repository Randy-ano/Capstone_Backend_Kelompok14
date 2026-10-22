<?php

namespace App\Http\Requests\LaporanKeluhan;

use App\Enums\StatusLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLaporanKeluhanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusLaporan::class)],
            'id_placement' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status laporan', 'id_placement' => 'placement'];
    }

    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'baru, diproses, atau selesai.', 'example' => 'baru'],
            'id_placement' => ['example' => 1],
            'search' => ['description' => 'Cari judul laporan atau nama talent.', 'example' => 'terlambat'],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
