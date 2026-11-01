<?php

namespace App\Enums;

/**
 * Status pengajuan yang ditampilkan ke pengguna: mengikuti tahap terakhir yang sudah ada di data.
 * Bukan kolom database; dihitung di PengajuanKandidat::statusTampil().
 */
enum StatusTampilPengajuan: string
{
    case Diajukan = 'diajukan';
    case LanjutInterview = 'lanjut_interview';
    case Ditolak = 'ditolak';
    case Diterima = 'diterima';
    case Ditempatkan = 'ditempatkan';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu Tinjauan',
            self::LanjutInterview => 'Lanjut Interview',
            self::Ditolak => 'Ditolak',
            self::Diterima => 'Diterima',
            self::Ditempatkan => 'Ditempatkan',
        };
    }
}
