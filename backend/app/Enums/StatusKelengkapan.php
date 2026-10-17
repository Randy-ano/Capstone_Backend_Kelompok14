<?php

namespace App\Enums;

enum StatusKelengkapan: string
{
    case BelumLengkap = 'belum_lengkap';
    case Lengkap = 'lengkap';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
