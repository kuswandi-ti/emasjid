<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function labels(): string
    {
        return match ($this) {
            self::Upcoming => 'Akan Datang',
            self::Ongoing => 'Sedang Berlangsung',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
