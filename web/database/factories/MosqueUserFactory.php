<?php

namespace Database\Factories;

use App\Models\Mosque;
use App\Models\MosqueUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MosqueUser>
 */
class MosqueUserFactory extends Factory
{
    protected $model = MosqueUser::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'user_id' => User::factory(),
            'joined_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
