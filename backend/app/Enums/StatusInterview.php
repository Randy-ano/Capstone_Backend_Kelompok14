<?php

namespace App\Enums;

enum StatusInterview: string
{
    case Dijadwalkan = 'dijadwalkan';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';
    case DijadwalUlang = 'dijadwal_ulang';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
