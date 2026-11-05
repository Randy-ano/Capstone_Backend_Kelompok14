<?php

namespace App\Http\Requests\Monitoring;

use App\Enums\TipeMonitoring;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoringRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['required', 'integer', 'exists:placement,id_placement'],
            'tipe' => ['nullable', Rule::enum(TipeMonitoring::class)],
            'id_dokumen' => ['nullable', 'integer'],
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
            'tipe' => 'jenis catatan',
            'id_dokumen' => 'dokumen Surat Peringatan',
            'tanggal_monitoring' => 'tanggal monitoring',
            'hasil_monitoring' => 'hasil monitoring',
            'catatan_tindak_lanjut' => 'catatan tindak lanjut',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'id_placement' => ['example' => 1],
            'tipe' => ['description' => 'catatan (default; HR Client & Manager) atau sp (Surat Peringatan, khusus Manager).', 'example' => 'catatan'],
            'id_dokumen' => ['description' => 'Wajib untuk sp: dokumen berjenis surat_peringatan milik talent penempatan ini.', 'example' => null],
            'tanggal_monitoring' => ['description' => 'Default: sekarang.', 'example' => '2026-10-22 10:00:00'],
            'hasil_monitoring' => ['example' => 'Kinerja baik, target sprint tercapai.'],
            'catatan_tindak_lanjut' => ['example' => 'Pertahankan; evaluasi ulang bulan depan.'],
        ];
    }
}
