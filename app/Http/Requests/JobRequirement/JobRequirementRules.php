<?php

namespace App\Http\Requests\JobRequirement;

/**
 * Pesan & nama atribut validasi yang sama untuk buat dan ubah job requirement.
 */
final class JobRequirementRules
{
    public const MESSAGES = [
        'sla_deadline.after' => 'SLA deadline harus setelah waktu sekarang.',
        'kompetensi.required' => 'Pilih minimal 1 kompetensi.',
        'kompetensi.min' => 'Pilih minimal 1 kompetensi.',
    ];

    public const ATTRIBUTES = [
        'id_klien' => 'klien',
        'posisi' => 'posisi',
        'jumlah_kebutuhan' => 'jumlah kebutuhan',
        'kualifikasi' => 'kualifikasi',
        'pengalaman_min' => 'pengalaman minimal',
        'sla_deadline' => 'SLA deadline',
        'status_req' => 'status job requirement',
        'kompetensi' => 'kompetensi',
        'kompetensi.*' => 'kompetensi',
    ];
}
