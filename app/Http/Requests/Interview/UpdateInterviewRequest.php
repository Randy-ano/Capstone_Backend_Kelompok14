<?php

namespace App\Http\Requests\Interview;

use App\Enums\StatusInterview;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Jadwal ulang (tanggal/waktu/link/instruksi) ATAU ubah status (selesai/dibatalkan), tidak sekaligus.
 */
class UpdateInterviewRequest extends FormRequest
{
    private const FIELD_JADWAL = ['tanggal_interview', 'waktu_interview', 'link_lokasi_meeting', 'instruksi'];

    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('interview'));
    }

    public function rules(): array
    {
        return [
            ...InterviewRules::jadwal(wajib: false),
            'status_interview' => ['sometimes', 'required', Rule::in([StatusInterview::Selesai->value, StatusInterview::Dibatalkan->value])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $jadwal = $this->hasAny(self::FIELD_JADWAL);
                if (! $jadwal && ! $this->has('status_interview')) {
                    $validator->errors()->add('status_interview', 'Kirim perubahan jadwal atau status interview.');
                } elseif ($jadwal && $this->has('status_interview')) {
                    $validator->errors()->add('status_interview', 'Ubah jadwal dan ubah status interview dilakukan terpisah.');
                }
            },
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
            'tanggal_interview' => ['description' => 'Jadwal ulang; status otomatis dijadwal_ulang jika tanggal/waktu berubah.', 'example' => '2026-10-30'],
            'waktu_interview' => ['example' => '13:30'],
            'link_lokasi_meeting' => ['example' => 'https://meet.google.com/abc-defg-hij'],
            'instruksi' => ['example' => 'Bawa KTP.'],
            'status_interview' => ['description' => 'selesai atau dibatalkan (final). Tidak bisa digabung dengan perubahan jadwal.', 'example' => 'selesai'],
        ];
    }
}
