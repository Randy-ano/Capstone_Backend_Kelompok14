<?php

namespace App\Enums;

enum JenisDokumen: string
{
    case Quotation = 'quotation';
    case PurchaseOrder = 'purchase_order';
    case Pks = 'pks';
    case KontrakKerja = 'kontrak_kerja';
    case SuratPeringatan = 'surat_peringatan';
    case Cv = 'cv';
    case Lainnya = 'lainnya';
    // Dibuat otomatis saat penempatan diakhiri (feedback SUS 2026-11-14); tidak bisa diunggah manual.
    case SuratSelesaiPenempatan = 'surat_selesai_penempatan';
    case SuratPemberhentianPenempatan = 'surat_pemberhentian_penempatan';

    /** Surat akhir penempatan (Selesai / Dihentikan): dibuat sistem, tidak terbuka untuk Talent Support. */
    public function akhirPenempatan(): bool
    {
        return in_array($this, self::AKHIR_PENEMPATAN, true);
    }

    public const AKHIR_PENEMPATAN = [self::SuratSelesaiPenempatan, self::SuratPemberhentianPenempatan];

    /** Jenis yang boleh diunggah manual (CV lewat profil talent, surat akhir penempatan dibuat sistem). @return list<string> */
    public static function bolehUnggah(): array
    {
        return array_values(array_diff(self::values(), [self::Cv->value, self::SuratSelesaiPenempatan->value, self::SuratPemberhentianPenempatan->value]));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
