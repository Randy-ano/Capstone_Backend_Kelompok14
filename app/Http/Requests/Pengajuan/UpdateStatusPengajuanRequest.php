<?php

namespace App\Http\Requests\Pengajuan;

use App\Enums\StatusPengajuan;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStatusPengajuanRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('updateStatus', $this->route('pengajuan'));
    }

    public function rules(): array
    {
        return [
            'status_pengajuan' => ['required', Rule::in([StatusPengajuan::LanjutInterview->value, StatusPengajuan::Ditolak->value])],
        ];
    }

    public function attributes(): array
    {
        return ['status_pengajuan' => 'status pengajuan'];
    }

    public function bodyParameters(): array
    {
        return [
            'status_pengajuan' => ['description' => 'lanjut_interview atau ditolak.', 'example' => 'lanjut_interview'],
        ];
    }
}
