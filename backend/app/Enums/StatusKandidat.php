<?php

namespace App\Enums;

enum StatusKandidat: string
{
    case Tersedia = 'tersedia';
    case DalamProses = 'dalam_proses';
    case Ditempatkan = 'ditempatkan';
    case TidakAktif = 'tidak_aktif';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
