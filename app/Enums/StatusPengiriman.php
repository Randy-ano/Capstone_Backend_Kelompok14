<?php

namespace App\Enums;

enum StatusPengiriman: string
{
    case Menunggu = 'menunggu';
    case Terkirim = 'terkirim';
    case Gagal = 'gagal';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
