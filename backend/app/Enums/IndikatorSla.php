<?php

namespace App\Enums;

enum IndikatorSla: string
{
    case Aman = 'aman';
    case Mendekati = 'mendekati';
    case Terlambat = 'terlambat';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
