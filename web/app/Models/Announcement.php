<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use App\Traits\BelongsToMosque;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id',
        'title',
        'content',
        'image',
        'status',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnnouncementStatus::class,
            'published_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The user who published this announcement.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
