<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Traits\BelongsToMosque;
use Database\Factories\CashTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    /** @use HasFactory<CashTransactionFactory> */
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id',
        'type',
        'amount',
        'description',
        'category',
        'transaction_date',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The user who recorded this transaction.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
