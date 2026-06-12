<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\CashTransaction;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashTransaction>
 */
class CashTransactionFactory extends Factory
{
    protected $model = CashTransaction::class;

    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'type' => fake()->randomElement(TransactionType::cases()),
            'amount' => fake()->numberBetween(10_000, 10_000_000),
            'description' => fake()->sentence(),
            'category' => fake()->optional()->randomElement([
                'Infaq Jumat',
                'Listrik',
                'Air',
                'Kebersihan',
                'Perbaikan',
                'Kegiatan',
            ]),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Create an income transaction.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
        ]);
    }

    /**
     * Create an expense transaction.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
        ]);
    }
}
