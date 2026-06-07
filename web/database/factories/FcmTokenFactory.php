<?php

namespace Database\Factories;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FcmToken>
 */
class FcmTokenFactory extends Factory
{
    protected $model = FcmToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(152),
            'device_type' => fake()->randomElement(['android', 'ios']),
        ];
    }

    /**
     * Set device type to Android.
     */
    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'android',
        ]);
    }

    /**
     * Set device type to iOS.
     */
    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'ios',
        ]);
    }
}
