<?php

namespace App\Http\Requests\Shift;

use App\Enums\JenisShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Input massal (R22): satu jenis shift untuk banyak tanggal.
 */
class StoreShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['required', 'integer', 'exists:placement,id_placement'],
            'tanggal' => ['required', 'array', 'min:1', 'max:62'],
            'tanggal.*' => ['required', 'date_format:Y-m-d', 'distinct'],
            'jenis_shift' => ['required', Rule::enum(JenisShift::class)],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Pilih minimal 1 tanggal shift.',
            'tanggal.min' => 'Pilih minimal 1 tanggal shift.',
            'tanggal.max' => 'Maksimal 62 tanggal dalam satu kali input.',
            'tanggal.*.date_format' => 'Format tanggal shift harus YYYY-MM-DD.',
            'tanggal.*.distinct' => 'Tanggal shift tidak boleh ganda.',
        ];
    }

    public function attributes(): array
    {
        return ['id_placement' => 'placement', 'jenis_shift' => 'jenis shift'];
    }

    public function bodyParameters(): array
    {
        return [
            'id_placement' => ['example' => 1],
            'tanggal' => ['description' => 'Daftar tanggal (Y-m-d); tanggal yang sudah punya shift diperbarui.', 'example' => ['2026-11-02', '2026-11-03']],
            'jenis_shift' => ['description' => 'pagi, siang, malam, atau libur.', 'example' => 'pagi'],
            'keterangan' => ['example' => 'WFO'],
        ];
    }
}
