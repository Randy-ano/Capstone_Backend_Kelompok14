<?php

namespace App\Enums;

/**
 * Jenis entri monitoring kinerja (revisi 2026-11-06): catatan (HR Client / Manager) atau Surat Peringatan (Manager).
 */
enum TipeMonitoring: string
{
    case Catatan = 'catatan';
    case Sp = 'sp';

    public function label(): string
    {
        return match ($this) {
            self::Catatan => 'Catatan',
            self::Sp => 'Surat Peringatan',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
