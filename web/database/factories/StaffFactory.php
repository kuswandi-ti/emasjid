<?php

namespace Database\Factories;

use App\Models\Mosque;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'user_id' => User::factory(),
            'position' => fake()->randomElement([
                'Ketua DKM',
                'Wakil Ketua',
                'Sekretaris',
                'Bendahara',
                'Humas',
                'Koordinator Kebersihan',
                'Koordinator Keamanan',
                'Imam Tetap',
                'Marbot',
            ]),
            'is_active' => true,
            'joined_at' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Indicate the staff is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
