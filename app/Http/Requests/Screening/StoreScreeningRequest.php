<?php

namespace App\Http\Requests\Screening;

use App\Models\ScreeningTalent;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreScreeningRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', ScreeningTalent::class);
    }

    public function rules(): array
    {
        return [
            'id_job_req' => ['required', 'integer', 'exists:job_requirement,id_job_req'],
            'id_talent' => ['required', 'integer', 'exists:talent,id_talent'],
            'catatan_screening' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_job_req' => 'job requirement',
            'id_talent' => 'talent',
            'catatan_screening' => 'catatan screening',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'id_job_req' => ['description' => 'Job requirement berstatus diajukan/diproses.', 'example' => 1],
            'id_talent' => ['description' => 'Talent di talent pool yang tersedia.', 'example' => 1],
            'catatan_screening' => ['example' => 'Kompetensi sesuai, jadwalkan wawancara internal.'],
        ];
    }
}
