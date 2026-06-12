<?php

namespace App\Models;

use App\Enums\MosqueStatus;
use Database\Factories\MosqueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mosque extends Model
{
    /** @use HasFactory<MosqueFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'description',
        'photo',
        'latitude',
        'longitude',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'qris_image',
        'invitation_code',
        'status',
        'admin_user_id',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MosqueStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The admin/owner of this mosque.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Jamaah (congregation members) who joined this mosque.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mosque_user')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * Prayer schedules for this mosque.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Activities for this mosque.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Cash transactions for this mosque.
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * Donations to this mosque.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Announcements for this mosque.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Staff members of this mosque.
     */
    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
