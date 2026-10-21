<?php

namespace App\Http\Requests\JobRequirement;

use App\Enums\IndikatorSla;
use App\Enums\StatusReq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter daftar job requirement; dipakai juga oleh tabel monitoring GET /progres.
 */
class IndexJobRequirementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(StatusReq::class)],
            'indikator_sla' => ['nullable', Rule::enum(IndikatorSla::class)],
            'id_klien' => ['nullable', 'integer', 'exists:klien,id_klien'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status job requirement'];
    }

    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'Cari berdasarkan posisi atau nama perusahaan klien.', 'example' => 'backend'],
            'status' => ['description' => 'Filter status: draft, diajukan, diproses, terpenuhi, dibatalkan.', 'example' => 'diajukan'],
            'indikator_sla' => ['description' => 'Filter indikator SLA saat ini: aman, mendekati, terlambat.', 'example' => 'terlambat'],
            'id_klien' => ['description' => 'Filter klien (diabaikan untuk HR Client; selalu klien miliknya).', 'example' => 1],
            'per_page' => ['description' => 'Jumlah data per halaman (1–100, default 15).', 'example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
