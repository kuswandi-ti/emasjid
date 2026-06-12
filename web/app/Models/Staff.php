<?php

namespace App\Models;

use App\Traits\BelongsToMosque;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use BelongsToMosque, HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'mosque_id',
        'user_id',
        'position',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'date',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The user who holds this staff position.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
