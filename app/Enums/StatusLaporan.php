<?php

namespace App\Enums;

enum StatusLaporan: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Selesai = 'selesai';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
