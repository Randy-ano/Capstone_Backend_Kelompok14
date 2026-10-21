<?php

namespace App\Http\Requests\TalentPool;

use App\Enums\StatusKetersediaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTalentPoolRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StatusKetersediaan::class)],
            'kompetensi' => ['nullable', 'array', 'max:20'],
            'kompetensi.*' => ['integer', 'distinct', 'exists:kompetensi,id_kompetensi'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status ketersediaan',
            'kompetensi' => 'kompetensi',
            'kompetensi.*' => 'kompetensi',
        ];
    }

    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'Cari berdasarkan nama, email, domisili, nama kompetensi, atau tag keahlian.', 'example' => 'laravel'],
            'status' => ['description' => 'Filter status ketersediaan: tersedia, tidak_tersedia.', 'example' => 'tersedia'],
            'kompetensi' => ['description' => 'Daftar id kompetensi; talent harus memiliki semuanya.', 'example' => [1, 4]],
            'kompetensi.*' => ['example' => 1],
            'per_page' => ['description' => 'Jumlah data per halaman (1–100, default 15).', 'example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
