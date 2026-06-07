<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function labels(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Confirmed => 'Dikonfirmasi',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
