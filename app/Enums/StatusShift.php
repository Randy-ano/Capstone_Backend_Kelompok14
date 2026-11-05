<?php

namespace App\Enums;

enum StatusShift: string
{
    case Aktif = 'aktif';
    case Dibatalkan = 'dibatalkan';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
