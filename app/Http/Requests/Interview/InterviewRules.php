<?php

namespace App\Http\Requests\Interview;

/**
 * Aturan jadwal interview yang sama untuk buat dan jadwal ulang.
 */
final class InterviewRules
{
    public const MESSAGES = [
        'tanggal_interview.after_or_equal' => 'Tanggal interview tidak boleh sebelum hari ini.',
        'waktu_interview.date_format' => 'Format waktu interview harus jam:menit, misalnya 10:00.',
    ];

    public const ATTRIBUTES = [
        'id_pengajuan' => 'pengajuan',
        'tanggal_interview' => 'tanggal interview',
        'waktu_interview' => 'waktu interview',
        'link_lokasi_meeting' => 'link/lokasi meeting',
        'instruksi' => 'instruksi',
        'status_interview' => 'status interview',
    ];

    /** @return array<string, list<string>> */
    public static function jadwal(bool $wajib): array
    {
        $ada = $wajib ? ['required'] : ['sometimes', 'required'];
        $opsional = $wajib ? ['nullable'] : ['sometimes', 'nullable'];

        return [
            'tanggal_interview' => [...$ada, 'date', 'after_or_equal:today'],
            'waktu_interview' => [...$ada, 'date_format:H:i'],
            'link_lokasi_meeting' => [...$opsional, 'string', 'max:255'],
            'instruksi' => [...$opsional, 'string', 'max:2000'],
        ];
    }
}
