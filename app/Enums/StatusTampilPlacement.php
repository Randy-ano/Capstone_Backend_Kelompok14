<?php

namespace App\Enums;

/**
 * Status penempatan yang ditampilkan ke pengguna (terutama talent), diturunkan dari status_placement dan tanggal.
 * Bukan kolom database; dihitung di Placement::statusTampil().
 */
enum StatusTampilPlacement: string
{
    case BelumAktif = 'belum_aktif';
    case Aktif = 'aktif';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::BelumAktif => 'Belum Aktif',
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
        };
    }
}
