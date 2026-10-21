<?php

namespace App\Http\Requests\Screening;

use App\Enums\HasilScreening;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexScreeningRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_job_req' => ['nullable', 'integer', 'exists:job_requirement,id_job_req'],
            'id_talent' => ['nullable', 'integer', 'exists:talent,id_talent'],
            'hasil' => ['nullable', Rule::enum(HasilScreening::class)],
            'belum_diajukan' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_job_req' => 'job requirement',
            'id_talent' => 'talent',
            'hasil' => 'hasil screening',
        ];
    }

    public function queryParameters(): array
    {
        return [
            'id_job_req' => ['description' => 'Filter job requirement.', 'example' => 1],
            'id_talent' => ['description' => 'Filter talent.', 'example' => 1],
            'hasil' => ['description' => 'Filter hasil: pending, lolos, tidak_lolos.', 'example' => 'lolos'],
            'belum_diajukan' => ['description' => 'Jika 1, hanya screening yang belum diajukan ke klien (untuk pengajuan, R14).', 'example' => 1],
            'search' => ['description' => 'Cari nama talent atau posisi.', 'example' => 'budi'],
            'per_page' => ['description' => 'Jumlah data per halaman (1–100, default 15).', 'example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
