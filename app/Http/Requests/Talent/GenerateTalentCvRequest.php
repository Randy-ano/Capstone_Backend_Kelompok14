<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTalentCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->talent !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
