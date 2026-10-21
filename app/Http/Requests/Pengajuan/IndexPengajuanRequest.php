<?php

namespace App\Http\Requests\Pengajuan;

use App\Enums\StatusPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPengajuanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusPengajuan::class)],
            'id_job_req' => ['nullable', 'integer', 'exists:job_requirement,id_job_req'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status pengajuan', 'id_job_req' => 'job requirement'];
    }

    public function queryParameters(): array
    {
        return [
            'status' => ['description' => 'diajukan, lanjut_interview, atau ditolak.', 'example' => 'diajukan'],
            'id_job_req' => ['example' => 1],
            'search' => ['description' => 'Cari nama talent atau posisi.', 'example' => 'budi'],
            'per_page' => ['description' => '1–100, default 15.', 'example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
