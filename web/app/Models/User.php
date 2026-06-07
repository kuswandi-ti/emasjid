<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'active_mosque_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_mosque_id' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The mosque this user currently has active (for tenant scope).
     */
    public function activeMosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class, 'active_mosque_id');
    }

    /**
     * The mosque administered by this user.
     */
    public function administeredMosque(): HasOne
    {
        return $this->hasOne(Mosque::class, 'admin_user_id');
    }

    /**
     * Mosques this user has joined as jamaah.
     */
    public function mosques(): BelongsToMany
    {
        return $this->belongsToMany(Mosque::class, 'mosque_user')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * Donations made by this user.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * FCM tokens for push notifications.
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Staff positions of this user.
     */
    public function staffPositions(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Cash transactions recorded by this user.
     */
    public function recordedTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'recorded_by');
    }

    /**
     * Announcements published by this user.
     */
    public function publishedAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'published_by');
    }
}
