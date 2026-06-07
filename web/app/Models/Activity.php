<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Traits\BelongsToMosque;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id',
        'title',
        'description',
        'speaker',
        'location',
        'start_date',
        'start_time',
        'end_time',
        'is_recurring',
        'recurrence_note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_recurring' => 'boolean',
            'status' => ActivityStatus::class,
        ];
    }
}
