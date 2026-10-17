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

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
