<?php

namespace App\Http\Requests\Cuti;

use Illuminate\Foundation\Http\FormRequest;

class StoreCutiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['required', 'integer', 'exists:placement,id_placement'],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan_cuti' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai cuti tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    public function attributes(): array
    {
        return ['id_placement' => 'placement', 'tanggal_mulai' => 'tanggal mulai', 'tanggal_selesai' => 'tanggal selesai', 'alasan_cuti' => 'alasan cuti'];
    }

    public function bodyParameters(): array
    {
        return [
            'id_placement' => ['description' => 'Placement aktif milik talent.', 'example' => 1],
            'tanggal_mulai' => ['example' => '2026-11-02'],
            'tanggal_selesai' => ['example' => '2026-11-03'],
            'alasan_cuti' => ['example' => 'Acara keluarga.'],
        ];
    }
}
