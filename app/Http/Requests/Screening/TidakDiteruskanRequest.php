<?php

namespace App\Http\Requests\Screening;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TidakDiteruskanRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('tinjau', $this->route('screening'));
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['alasan' => 'alasan tidak diteruskan'];
    }

    public function bodyParameters(): array
    {
        return [
            'alasan' => ['description' => 'Alasan usulan tidak diteruskan ke klien (maks. 1000 karakter).', 'example' => 'Klien meminta lulusan S1 Informatika.'],
        ];
    }
}
