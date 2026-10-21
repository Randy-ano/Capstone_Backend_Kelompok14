<?php

namespace App\Http\Requests\Placement;

use App\Enums\StatusPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlacementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusPlacement::class)],
            'id_klien' => ['nullable', 'integer', 'exists:klien,id_klien'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status placement', 'id_klien' => 'klien'];
    }

    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'aktif, selesai, atau dihentikan.', 'example' => 'aktif'],
            'id_klien' => ['description' => 'Hanya untuk internal; diabaikan untuk HR Client & Talent.', 'example' => 1],
            'search' => ['description' => 'Cari nama talent, posisi, atau klien.', 'example' => 'budi'],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
