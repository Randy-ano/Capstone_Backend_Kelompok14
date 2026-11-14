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
            'alasan_penghentian' => ['required_if:status_placement,dihentikan', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_penghentian.required_if' => 'Alasan penghentian wajib diisi bila penempatan dihentikan.',
            'alasan_penghentian.max' => 'Alasan penghentian maksimal 1000 karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['status_placement' => 'status penempatan', 'tanggal_selesai' => 'tanggal selesai', 'alasan_penghentian' => 'alasan penghentian'];
    }

    public function bodyParameters(): array
    {
        return [
            'status_placement' => ['description' => 'selesai atau dihentikan.', 'example' => 'selesai'],
            'tanggal_selesai' => ['description' => 'Default: tanggal selesai kontrak, atau hari ini jika kosong.', 'example' => '2026-12-31'],
            'alasan_penghentian' => ['description' => 'Wajib jika dihentikan (maks. 1000 karakter); dicetak di Surat Pemberhentian Penempatan. Diabaikan untuk selesai.', 'example' => 'Klien mengakhiri proyek lebih awal.'],
        ];
    }
}
