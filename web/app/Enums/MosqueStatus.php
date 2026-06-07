<?php

namespace App\Enums;

enum MosqueStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function labels(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
            self::Rejected => 'Ditolak',
        };
    }
}
