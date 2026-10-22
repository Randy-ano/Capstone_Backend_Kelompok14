<?php

namespace App\Http\Requests\LaporanKeluhan;

use App\Enums\StatusLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TindakLanjutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_laporan' => ['required', Rule::in([StatusLaporan::Diproses->value, StatusLaporan::Selesai->value])],
            // Hasil wajib saat laporan diselesaikan.
            'hasil_tindak_lanjut' => ['nullable', 'required_if:status_laporan,selesai', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['hasil_tindak_lanjut.required_if' => 'Hasil tindak lanjut wajib diisi saat laporan diselesaikan.'];
    }

    public function attributes(): array
    {
        return ['status_laporan' => 'status laporan', 'hasil_tindak_lanjut' => 'hasil tindak lanjut'];
    }

    public function bodyParameters(): array
    {
        return [
            'status_laporan' => ['description' => 'diproses atau selesai.', 'example' => 'selesai'],
            'hasil_tindak_lanjut' => ['example' => 'Talent sudah ditegur dan berkomitmen hadir tepat waktu.'],
        ];
    }
}
