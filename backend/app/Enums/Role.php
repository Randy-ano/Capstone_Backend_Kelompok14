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
}
