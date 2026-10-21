<?php

namespace App\Enums;

enum StatusDokumen: string
{
    case Draft = 'draft';
    case MenungguTtd = 'menunggu_ttd';
    case Ditandatangani = 'ditandatangani';
    case Ditolak = 'ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
