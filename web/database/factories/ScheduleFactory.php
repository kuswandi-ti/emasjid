<?php

namespace Database\Factories;

use App\Models\Mosque;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'date' => fake()->dateTimeBetween('now', '+7 days')->format('Y-m-d'),
            'subuh' => '04:30',
            'subuh_iqomah' => '04:40',
            'dzuhur' => '12:00',
            'dzuhur_iqomah' => '12:10',
            'ashar' => '15:15',
            'ashar_iqomah' => '15:25',
            'maghrib' => '18:00',
            'maghrib_iqomah' => '18:05',
            'isya' => '19:15',
            'isya_iqomah' => '19:25',
            'jumat_time' => null,
            'jumat_khatib' => null,
            'jumat_imam' => null,
        ];
    }

    /**
     * Indicate this is a Friday schedule with Jumat prayer details.
     */
    public function friday(): static
    {
        return $this->state(fn (array $attributes) => [
            'jumat_time' => '12:00',
            'jumat_khatib' => fake()->name(),
            'jumat_imam' => fake()->name(),
        ]);
    }
}
