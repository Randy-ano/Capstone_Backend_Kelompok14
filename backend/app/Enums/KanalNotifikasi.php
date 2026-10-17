<?php

namespace App\Enums;

enum KanalNotifikasi: string
{
    case Sistem = 'sistem';
    case Email = 'email';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
