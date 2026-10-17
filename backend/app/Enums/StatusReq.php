<?php

namespace App\Enums;

enum StatusReq: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case Diproses = 'diproses';
    case Terpenuhi = 'terpenuhi';
    case Dibatalkan = 'dibatalkan';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
