<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Diajukan = 'diajukan';
    case LanjutInterview = 'lanjut_interview';
    case Ditolak = 'ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
