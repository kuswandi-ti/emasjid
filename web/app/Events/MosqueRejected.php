<?php

namespace App\Events;

use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;

class MosqueRejected
{
    use SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public int $rejectedByUserId,
        public string $rejectionReason,
        public Carbon $rejectedAt,
    ) {}
}
