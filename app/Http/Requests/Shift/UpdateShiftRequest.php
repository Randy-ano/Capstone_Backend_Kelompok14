<?php

namespace App\Http\Requests\Shift;

use App\Enums\JenisShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal_shift' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'jenis_shift' => ['sometimes', 'required', Rule::enum(JenisShift::class)],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['tanggal_shift', 'jenis_shift', 'keterangan'])) {
                    $validator->errors()->add('jenis_shift', 'Kirim minimal satu kolom: tanggal, jenis shift, atau keterangan.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return ['tanggal_shift.date_format' => 'Format tanggal shift harus YYYY-MM-DD.'];
    }

    public function attributes(): array
    {
        return ['tanggal_shift' => 'tanggal shift', 'jenis_shift' => 'jenis shift'];
    }

    public function bodyParameters(): array
    {
        return [
            'tanggal_shift' => ['example' => '2026-11-04'],
            'jenis_shift' => ['example' => 'malam'],
            'keterangan' => ['example' => null],
        ];
    }
}
