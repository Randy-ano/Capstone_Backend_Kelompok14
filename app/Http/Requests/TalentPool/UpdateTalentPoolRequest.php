<?php

namespace App\Http\Requests\TalentPool;

use App\Enums\StatusKetersediaan;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTalentPoolRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('talentPool'));
    }

    public function rules(): array
    {
        return [
            'status_ketersediaan' => ['sometimes', 'required', Rule::enum(StatusKetersediaan::class)],
            'tag_keahlian' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['status_ketersediaan', 'tag_keahlian'])) {
                    $validator->errors()->add('status_ketersediaan', 'Kirim minimal satu kolom: status ketersediaan atau tag keahlian.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'status_ketersediaan' => 'status ketersediaan',
            'tag_keahlian' => 'tag keahlian',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status_ketersediaan' => ['description' => 'Salah satu: tersedia, tidak_tersedia.', 'example' => 'tidak_tersedia'],
            'tag_keahlian' => ['description' => 'Label bebas untuk pencarian cepat; null untuk mengosongkan.', 'example' => 'Laravel, MySQL, REST API'],
        ];
    }
}
