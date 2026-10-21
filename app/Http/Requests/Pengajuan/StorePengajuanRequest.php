<?php

namespace App\Http\Requests\Pengajuan;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_screening' => ['required', 'integer', 'exists:screening_talent,id_screening'],
        ];
    }

    public function attributes(): array
    {
        return ['id_screening' => 'screening'];
    }

    public function bodyParameters(): array
    {
        return [
            'id_screening' => ['description' => 'Screening berhasil lolos yang belum diajukan.', 'example' => 1],
        ];
    }
}
