<?php

namespace App\Http\Requests\Placement;

use App\Enums\StatusPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusPlacementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_placement' => ['required', Rule::in([StatusPlacement::Selesai->value, StatusPlacement::Dihentikan->value])],
            'tanggal_selesai' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return ['status_placement' => 'status placement', 'tanggal_selesai' => 'tanggal selesai'];
    }

    public function bodyParameters(): array
    {
        return [
            'status_placement' => ['description' => 'selesai atau dihentikan.', 'example' => 'selesai'],
            'tanggal_selesai' => ['description' => 'Default: tanggal selesai kontrak, atau hari ini jika kosong.', 'example' => '2026-12-31'],
        ];
    }
}
