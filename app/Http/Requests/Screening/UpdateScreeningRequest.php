<?php

namespace App\Http\Requests\Screening;

use App\Enums\HasilScreening;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateScreeningRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('screening'));
    }

    public function rules(): array
    {
        return [
            'hasil_screening' => ['sometimes', 'required', Rule::enum(HasilScreening::class)],
            'catatan_screening' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['hasil_screening', 'catatan_screening'])) {
                    $validator->errors()->add('hasil_screening', 'Kirim minimal satu kolom: hasil screening atau catatan screening.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'hasil_screening' => 'hasil screening',
            'catatan_screening' => 'catatan screening',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'hasil_screening' => ['description' => 'pending, lolos, atau tidak_lolos (final).', 'example' => 'lolos'],
            'catatan_screening' => ['example' => 'Lolos tes teknis.'],
        ];
    }
}
