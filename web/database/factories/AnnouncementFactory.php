<?php

namespace Database\Factories;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'image' => null,
            'status' => AnnouncementStatus::Draft,
            'published_at' => null,
            'published_by' => null,
        ];
    }

    /**
     * Indicate the announcement is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Published,
            'published_at' => now(),
            'published_by' => User::factory(),
        ]);
    }

    /**
     * Indicate the announcement is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::Archived,
            'published_at' => now()->subDays(30),
            'published_by' => User::factory(),
        ]);
    }
}
