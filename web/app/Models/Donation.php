<?php

namespace App\Models;

use App\Enums\DonationCategory;
use App\Enums\DonationStatus;
use App\Traits\BelongsToMosque;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    /** @use HasFactory<DonationFactory> */
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id',
        'user_id',
        'category',
        'amount',
        'fee_amount',
        'payment_amount',
        'mosque_receives',
        'fee_mechanism',
        'status',
        'is_anonymous',
        'merchant_order_id',
        'payment_url',
        'reference',
        'payment_method',
        'confirmed_at',
        'expired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => DonationCategory::class,
            'status' => DonationStatus::class,
            'amount' => 'integer',
            'fee_amount' => 'integer',
            'payment_amount' => 'integer',
            'mosque_receives' => 'integer',
            'is_anonymous' => 'boolean',
            'confirmed_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    // ─── Query Scopes ────────────────────────────────────────────────

    /**
     * Scope untuk donasi dengan status confirmed.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope untuk donasi dalam periode bulan tertentu (berdasarkan confirmed_at).
     */
    public function scopeInPeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->whereMonth('confirmed_at', $month)
                     ->whereYear('confirmed_at', $year);
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The donor (user) who made this donation.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
