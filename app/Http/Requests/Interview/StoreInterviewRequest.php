<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_pengajuan' => ['required', 'integer', 'exists:pengajuan_kandidat,id_pengajuan'],
            ...InterviewRules::jadwal(wajib: true),
        ];
    }

    public function messages(): array
    {
        return InterviewRules::MESSAGES;
    }

    public function attributes(): array
    {
        return InterviewRules::ATTRIBUTES;
    }

    public function bodyParameters(): array
    {
        return [
            'id_pengajuan' => ['description' => 'Pengajuan berstatus lanjut_interview.', 'example' => 1],
            'tanggal_interview' => ['description' => 'Hari ini atau setelahnya (Y-m-d).', 'example' => '2026-10-28'],
            'waktu_interview' => ['description' => 'Format H:i.', 'example' => '10:00'],
            'link_lokasi_meeting' => ['example' => 'https://meet.google.com/abc-defg-hij'],
            'instruksi' => ['example' => 'Siapkan portofolio proyek terakhir.'],
        ];
    }
}
