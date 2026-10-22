<?php

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonitoringRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['required', 'integer', 'exists:placement,id_placement'],
            'tanggal_monitoring' => ['nullable', 'date', 'before_or_equal:now'],
            'hasil_monitoring' => ['required', 'string', 'max:5000'],
            'catatan_tindak_lanjut' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['tanggal_monitoring.before_or_equal' => 'Tanggal monitoring tidak boleh di masa depan.'];
    }

    public function attributes(): array
    {
        return [
            'id_placement' => 'placement',
            'tanggal_monitoring' => 'tanggal monitoring',
            'hasil_monitoring' => 'hasil monitoring',
            'catatan_tindak_lanjut' => 'catatan tindak lanjut',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'id_placement' => ['example' => 1],
            'tanggal_monitoring' => ['description' => 'Default: sekarang.', 'example' => '2026-10-22 10:00:00'],
            'hasil_monitoring' => ['example' => 'Kinerja baik, target sprint tercapai.'],
            'catatan_tindak_lanjut' => ['example' => 'Pertahankan; evaluasi ulang bulan depan.'],
        ];
    }
}
