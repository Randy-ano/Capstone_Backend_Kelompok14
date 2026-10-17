<?php

namespace App\Enums;

enum StatusKetersediaan: string
{
    case Tersedia = 'tersedia';
    case TidakTersedia = 'tidak_tersedia';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
