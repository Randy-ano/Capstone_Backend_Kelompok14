<?php

namespace App\Http\Requests\Evaluasi;

use App\Enums\StatusEvaluasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluasiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_pengajuan' => ['required', 'integer', 'exists:pengajuan_kandidat,id_pengajuan'],
            'nilai_score' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_evaluasi' => ['nullable', 'string', 'max:5000'],
            'status_evaluasi' => ['required', Rule::enum(StatusEvaluasi::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_pengajuan' => 'pengajuan',
            'nilai_score' => 'nilai',
            'catatan_evaluasi' => 'catatan evaluasi',
            'status_evaluasi' => 'hasil evaluasi',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'id_pengajuan' => ['description' => 'Pengajuan lanjut_interview dengan minimal 1 interview selesai.', 'example' => 1],
            'nilai_score' => ['description' => '0–100.', 'example' => 85],
            'catatan_evaluasi' => ['example' => 'Komunikasi baik, teknis kuat.'],
            'status_evaluasi' => ['description' => 'diterima atau ditolak.', 'example' => 'diterima'],
        ];
    }
}
