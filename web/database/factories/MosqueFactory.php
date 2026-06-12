<?php

namespace Database\Factories;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mosque>
 */
class MosqueFactory extends Factory
{
    protected $model = Mosque::class;

    public function definition(): array
    {
        $name = 'Masjid '.fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'description' => fake()->optional()->paragraph(),
            'photo' => null,
            'latitude' => fake()->optional()->latitude(-8.5, -6.0),
            'longitude' => fake()->optional()->longitude(106.0, 112.0),
            'bank_name' => fake()->optional()->company(),
            'bank_account_name' => fake()->optional()->name(),
            'bank_account_number' => fake()->optional()->numerify('###########'),
            'qris_image' => null,
            'invitation_code' => strtoupper(Str::random(8)),
            'status' => MosqueStatus::Active,
            'admin_user_id' => User::factory(),
            'rejection_reason' => null,
            'approved_at' => now(),
        ];
    }

    /**
     * Indicate that the mosque is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MosqueStatus::Pending,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the mosque is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MosqueStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the mosque is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MosqueStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
            'approved_at' => null,
        ]);
    }
}
