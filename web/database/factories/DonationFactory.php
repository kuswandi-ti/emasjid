<?php

namespace Database\Factories;

use App\Enums\DonationCategory;
use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(10_000, 5_000_000);
        $feeMechanism = fake()->randomElement(['added_to_donor', 'deducted_from_donation']);
        $feeAmount = (int) round($amount * 0.025);

        if ($feeMechanism === 'added_to_donor') {
            $paymentAmount = $amount + $feeAmount;
            $mosqueReceives = $amount;
        } else {
            $paymentAmount = $amount;
            $mosqueReceives = $amount - $feeAmount;
        }

        return [
            'mosque_id' => Mosque::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(DonationCategory::cases()),
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'payment_amount' => $paymentAmount,
            'mosque_receives' => $mosqueReceives,
            'fee_mechanism' => $feeMechanism,
            'status' => DonationStatus::Pending,
            'is_anonymous' => fake()->boolean(20),
            'merchant_order_id' => 'ORD-'.strtoupper(Str::random(12)),
            'payment_url' => fake()->optional()->url(),
            'reference' => null,
            'payment_method' => null,
            'confirmed_at' => null,
            'expired_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate the donation is confirmed/paid.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::Confirmed,
            'confirmed_at' => now(),
            'reference' => 'REF-'.strtoupper(Str::random(10)),
            'payment_method' => fake()->randomElement(['QRIS', 'VA_BCA', 'VA_BNI', 'VA_MANDIRI']),
        ]);
    }

    /**
     * Indicate the donation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::Expired,
            'expired_at' => now(),
        ]);
    }

    /**
     * Indicate the donation has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DonationStatus::Failed,
        ]);
    }

    /**
     * Create an anonymous donation.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_anonymous' => true,
        ]);
    }
}
