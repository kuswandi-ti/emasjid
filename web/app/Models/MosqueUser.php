<?php

namespace App\Models;

use Database\Factories\MosqueUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MosqueUser extends Pivot
{
    /** @use HasFactory<MosqueUserFactory> */
    use HasFactory;

    protected $table = 'mosque_user';

    public $incrementing = true;

    protected $fillable = [
        'mosque_id',
        'user_id',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
