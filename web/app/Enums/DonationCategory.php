<?php

namespace App\Enums;

enum DonationCategory: string
{
    case Infaq = 'infaq';
    case Zakat = 'zakat';
    case Sadaqah = 'sadaqah';
    case Waqf = 'waqf';

    public function labels(): string
    {
        return match ($this) {
            self::Infaq => 'Infaq',
            self::Zakat => 'Zakat',
            self::Sadaqah => 'Sedekah',
            self::Waqf => 'Wakaf',
        };
    }
}
