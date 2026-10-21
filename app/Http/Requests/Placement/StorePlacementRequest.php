<?php

namespace App\Http\Requests\Placement;

use Illuminate\Foundation\Http\FormRequest;

class StorePlacementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_evaluasi' => ['required', 'integer', 'exists:evaluasi_kandidat,id_evaluasi'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'posisi_penempatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.'];
    }

    public function attributes(): array
    {
        return [
            'id_evaluasi' => 'evaluasi',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
            'posisi_penempatan' => 'posisi penempatan',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'id_evaluasi' => ['description' => 'Evaluasi diterima yang belum punya placement.', 'example' => 1],
            'tanggal_mulai' => ['example' => '2026-11-01'],
            'tanggal_selesai' => ['description' => 'Akhir kontrak, boleh kosong.', 'example' => '2027-10-31'],
            'posisi_penempatan' => ['description' => 'Default: posisi job requirement.', 'example' => 'Backend Developer'],
        ];
    }
}
