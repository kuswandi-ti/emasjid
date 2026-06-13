<?php

namespace App\Events;

use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MosqueSuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Mosque $mosque,
        public int    $actorUserId,
        public Carbon $actedAt,
    ) {}
}
