<?php

namespace App\Http\Requests\Interview;

use App\Enums\StatusInterview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInterviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'status' => ['nullable', Rule::enum(StatusInterview::class)],
            'id_pengajuan' => ['nullable', 'integer', 'exists:pengajuan_kandidat,id_pengajuan'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['sampai.after_or_equal' => 'Tanggal "sampai" tidak boleh sebelum tanggal "dari".'];
    }

    public function attributes(): array
    {
        return ['dari' => 'tanggal dari', 'sampai' => 'tanggal sampai', 'status' => 'status interview', 'id_pengajuan' => 'pengajuan'];
    }

    public function queryParameters(): array
    {
        return [
            'dari' => ['description' => 'Tanggal interview mulai (Y-m-d).', 'example' => '2026-10-01'],
            'sampai' => ['description' => 'Tanggal interview sampai (Y-m-d).', 'example' => '2026-10-31'],
            'status' => ['description' => 'dijadwalkan, selesai, dibatalkan, dijadwal_ulang.', 'example' => 'dijadwalkan'],
            'id_pengajuan' => ['example' => 1],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
