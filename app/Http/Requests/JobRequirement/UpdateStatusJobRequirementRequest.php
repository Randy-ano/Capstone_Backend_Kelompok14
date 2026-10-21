<?php

namespace App\Http\Requests\JobRequirement;

use App\Enums\StatusReq;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStatusJobRequirementRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('jobRequirement'));
    }

    public function rules(): array
    {
        return [
            'status_req' => ['required', Rule::in([StatusReq::Diajukan->value, StatusReq::Dibatalkan->value])],
        ];
    }

    public function attributes(): array
    {
        return ['status_req' => 'status job requirement'];
    }

    public function bodyParameters(): array
    {
        return [
            'status_req' => ['description' => 'diajukan (dari draft) atau dibatalkan (dari draft/diajukan/diproses).', 'example' => 'diajukan'],
        ];
    }
}
