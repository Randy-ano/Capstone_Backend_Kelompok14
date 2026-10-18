<?php

namespace App\Enums;

enum StatusEvaluasi: string
{
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
