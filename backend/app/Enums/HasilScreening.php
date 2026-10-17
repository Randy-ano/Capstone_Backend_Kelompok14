<?php

namespace App\Enums;

enum HasilScreening: string
{
    case Pending = 'pending';
    case Lolos = 'lolos';
    case TidakLolos = 'tidak_lolos';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
