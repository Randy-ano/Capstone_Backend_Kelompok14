<?php

namespace App\Http\Requests\Cuti;

use App\Enums\StatusApproval;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ApprovalCutiRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('approve', $this->route('cuti'));
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in([StatusApproval::Disetujui->value, StatusApproval::Ditolak->value])],
        ];
    }

    public function bodyParameters(): array
    {
        return ['keputusan' => ['description' => 'disetujui atau ditolak.', 'example' => 'disetujui']];
    }
}
