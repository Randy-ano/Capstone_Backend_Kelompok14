<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tanpa body. Role talent dicek middleware route; syarat profil lengkap dicek di TalentCvService.
 */
class GenerateTalentCvRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
