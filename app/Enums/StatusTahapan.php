<?php

namespace App\Enums;

enum StatusTahapan: string
{
    case KebutuhanDibuat = 'kebutuhan_dibuat';
    case Screening = 'screening';
    case Diajukan = 'diajukan';
    case Interview = 'interview';
    case Evaluasi = 'evaluasi';
    case Placement = 'placement';
    case Ditolak = 'ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
