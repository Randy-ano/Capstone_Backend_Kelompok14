<?php

namespace App\Enums;

enum Role: string
{
    case Talent = 'talent';
    case HrClient = 'hr_client';
    case AccountManager = 'account_manager';
    case TalentSupport = 'talent_support';
    case Manager = 'manager';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Role pegawai PT Intellisys (AM, TS, Manager).
     *
     * @return list<self>
     */
    public static function internal(): array
    {
        return [self::AccountManager, self::TalentSupport, self::Manager];
    }

    /** Nama peran untuk ditampilkan (Bahasa Indonesia). */
    public function label(): string
    {
        return match ($this) {
            self::Talent => 'Talent',
            self::HrClient => 'HR Client',
            self::AccountManager => 'Account Manager',
            self::TalentSupport => 'Talent Support',
            self::Manager => 'Manager',
        };
    }

    public function isInternal(): bool
    {
        return in_array($this, self::internal(), true);
    }
}
