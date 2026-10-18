<?php

namespace App\Enums;

enum JenisShift: string
{
    case Pagi = 'pagi';
    case Siang = 'siang';
    case Malam = 'malam';
    case Libur = 'libur';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
