<?php

namespace App\Enums;

enum StatusTtd: string
{
    case Menunggu = 'menunggu';
    case Ditandatangani = 'ditandatangani';
    case Ditolak = 'ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
