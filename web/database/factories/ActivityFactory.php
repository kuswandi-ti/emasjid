<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Mosque;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'speaker' => fake()->optional()->name(),
            'location' => fake()->optional()->address(),
            'start_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => fake()->optional()->time('H:i'),
            'end_time' => fake()->optional()->time('H:i'),
            'is_recurring' => false,
            'recurrence_note' => null,
            'status' => ActivityStatus::Upcoming,
        ];
    }

    /**
     * Indicate this is a recurring activity.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurrence_note' => fake()->randomElement([
                'Setiap Senin',
                'Setiap Jumat',
                'Setiap Minggu',
                'Setiap hari',
            ]),
        ]);
    }

    /**
     * Indicate the activity is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'status' => ActivityStatus::Completed,
        ]);
    }

    /**
     * Indicate the activity is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivityStatus::Cancelled,
        ]);
    }
}
