<?php

namespace App\Models;

use App\Traits\BelongsToMosque;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use BelongsToMosque, HasFactory;

    protected $fillable = [
        'mosque_id',
        'date',
        'subuh',
        'subuh_iqomah',
        'dzuhur',
        'dzuhur_iqomah',
        'ashar',
        'ashar_iqomah',
        'maghrib',
        'maghrib_iqomah',
        'isya',
        'isya_iqomah',
        'jumat_time',
        'jumat_khatib',
        'jumat_imam',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
