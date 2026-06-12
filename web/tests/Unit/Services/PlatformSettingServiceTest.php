<?php

namespace Tests\Unit\Services;

use App\Services\PlatformSettingService;
use Tests\TestCase;

class PlatformSettingServiceTest extends TestCase
{
    private PlatformSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlatformSettingService::class);
    }

    public function test_get_fee_settings_returns_array_with_required_keys(): void
    {
        $settings = $this->service->getFeeSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('fee_percentage', $settings);
        $this->assertArrayHasKey('fee_mechanism', $settings);
        $this->assertArrayHasKey('fee_active', $settings);
    }

    public function test_calculate_fee_with_added_to_donor_mechanism(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250, // 2.5%
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(2500, $result['fee_amount']);
        $this->assertEquals(102500, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }

    public function test_calculate_fee_with_deducted_from_donation_mechanism(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250, // 2.5%
            'fee_mechanism' => 'deducted_from_donation',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(2500, $result['fee_amount']);
        $this->assertEquals(100000, $result['payment_amount']);
        $this->assertEquals(97500, $result['mosque_receives']);
    }

    public function test_calculate_fee_when_fee_is_inactive(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => false,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(0, $result['fee_amount']);
        $this->assertEquals(100000, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }

    public function test_calculate_fee_with_different_percentage(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 500, // 5%
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        $this->assertEquals(5000, $result['fee_amount']);
        $this->assertEquals(105000, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }

    public function test_calculate_fee_rounds_correctly(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 333, // 3.33%
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);

        $result = $this->service->calculateFee(100000);

        // (100000 * 333) / 10000 = 3330
        $this->assertEquals(3330, $result['fee_amount']);
        $this->assertEquals(103330, $result['payment_amount']);
        $this->assertEquals(100000, $result['mosque_receives']);
    }

    public function test_get_fee_preview_returns_formatted_values(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 250,
            'fee_mechanism' => 'added_to_donor',
            'fee_active' => true,
        ]);

        $preview = $this->service->getFeePreview(100000);

        $this->assertArrayHasKey('settings', $preview);
        $this->assertArrayHasKey('sample_amount', $preview);
        $this->assertArrayHasKey('sample_amount_formatted', $preview);
        $this->assertArrayHasKey('fee_amount', $preview);
        $this->assertArrayHasKey('fee_amount_formatted', $preview);
        $this->assertArrayHasKey('payment_amount', $preview);
        $this->assertArrayHasKey('payment_amount_formatted', $preview);
        $this->assertArrayHasKey('mosque_receives', $preview);
        $this->assertArrayHasKey('mosque_receives_formatted', $preview);
        $this->assertArrayHasKey('mechanism_label', $preview);

        $this->assertEquals(100000, $preview['sample_amount']);
        $this->assertStringContainsString('100.000', $preview['sample_amount_formatted']);
    }

    public function test_update_fee_settings_persists_values(): void
    {
        $this->service->updateFeeSettings([
            'fee_percentage' => 350,
            'fee_mechanism' => 'deducted_from_donation',
            'fee_active' => false,
        ]);

        $settings = $this->service->getFeeSettings();

        $this->assertEquals(350, $settings['fee_percentage']);
        $this->assertEquals('deducted_from_donation', $settings['fee_mechanism']);
        $this->assertFalse($settings['fee_active']);
    }

    // Feature: day9-platform-settings-fee-config, Property 3: Floor Semantics
    public function test_floor_semantics_for_fee_calculation(): void
    {
        // Property 3: fee_amount must equal floor(amount * pct / 10000), NOT round()
        // Edge case: amount=10001, pct=5000 → 10001 * 5000 / 10000 = 5000.5
        //   floor(5000.5) = 5000, but round(5000.5) = 5001
        // Validates: Requirements 4.3, 5.1
        foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => true,
            ]);
            $result   = $this->service->calculateFee($amount);
            $expected = (int) floor($amount * $pct / 10000);
            $this->assertEquals(
                $expected,
                $result['fee_amount'],
                "Property 3 (Floor Semantics) failed: expected floor({$amount} * {$pct} / 10000) = {$expected}, got {$result['fee_amount']}"
            );
        }

        // Explicit edge case: 10001 * 5000 / 10000 = 5000.5 → floor = 5000 (not round = 5001)
        $this->service->updateFeeSettings([
            'fee_percentage' => 5000,
            'fee_mechanism'  => 'added_to_donor',
            'fee_active'     => true,
        ]);
        $edgeResult = $this->service->calculateFee(10001);
        $this->assertEquals(5000, $edgeResult['fee_amount'],
            'Property 3 edge case failed: 10001 * 5000 / 10000 = 5000.5 should floor to 5000, not round to 5001');
    }

    // Feature: day9-platform-settings-fee-config, Property 1: Fee Identity
    public function test_fee_identity_zero_percentage_always_yields_zero_fee(): void
    {
        // Property 1: For any valid Donation_Amount with fee_percentage=0 and fee_active=true,
        // fee_amount must be 0
        // Validates: Requirement 5.3
        foreach ($this->randomAmounts(50) as $amount) {
            $this->service->updateFeeSettings([
                'fee_percentage' => 0,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => true,
            ]);
            $result = $this->service->calculateFee($amount);
            $this->assertEquals(
                0,
                $result['fee_amount'],
                "Property 1 (Fee Identity) failed: fee_amount should be 0 when fee_percentage=0, got {$result['fee_amount']} for amount={$amount}"
            );
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 2: Fee Inactive
    public function test_fee_inactive_returns_zero_fee_for_any_input(): void
    {
        // Property 2: For any valid Donation_Amount and Fee_Percentage, if fee_active=false,
        // fee_amount=0, payment_amount=donation_amount, mosque_receives=donation_amount
        // Validates: Requirements 4.4, 5.2
        foreach ($this->randomFeeConfigs(50) as [$amount, $pct]) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => false,
            ]);
            $result = $this->service->calculateFee($amount);
            $this->assertEquals(0, $result['fee_amount'],
                "Property 2 (Fee Inactive) failed: fee_amount should be 0 when inactive, got {$result['fee_amount']} for amount={$amount}, pct={$pct}");
            $this->assertEquals($amount, $result['payment_amount'],
                "Property 2 (Fee Inactive) failed: payment_amount should equal donation_amount={$amount}, got {$result['payment_amount']}");
            $this->assertEquals($amount, $result['mosque_receives'],
                "Property 2 (Fee Inactive) failed: mosque_receives should equal donation_amount={$amount}, got {$result['mosque_receives']}");
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 4: added_to_donor Invariant
    public function test_added_to_donor_payment_equals_amount_plus_fee(): void
    {
        // Property 4: For added_to_donor mechanism with fee_active=true,
        // payment_amount = donation_amount + fee_amount, mosque_receives = donation_amount
        // Validates: Requirements 6.1, 6.2
        foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => true,
            ]);
            $result = $this->service->calculateFee($amount);
            $this->assertEquals(
                $amount + $result['fee_amount'],
                $result['payment_amount'],
                "Property 4 (added_to_donor) failed: payment_amount should be donation_amount + fee_amount for amount={$amount}, pct={$pct}"
            );
            $this->assertEquals(
                $amount,
                $result['mosque_receives'],
                "Property 4 (added_to_donor) failed: mosque_receives should equal donation_amount={$amount}, got {$result['mosque_receives']}"
            );
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 5: deducted_from_donation Invariant
    public function test_deducted_from_donation_invariant(): void
    {
        // Property 5: For deducted_from_donation with fee_active=true,
        // payment_amount = donation_amount, mosque_receives = max(0, donation_amount - fee_amount),
        // and mosque_receives >= 0 always
        // Validates: Requirements 7.1, 7.2, 7.3
        foreach ($this->randomFeeConfigs(100) as [$amount, $pct]) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism'  => 'deducted_from_donation',
                'fee_active'     => true,
            ]);
            $result = $this->service->calculateFee($amount);
            $this->assertEquals(
                $amount,
                $result['payment_amount'],
                "Property 5 (deducted_from_donation) failed: payment_amount should equal donation_amount={$amount}"
            );
            $this->assertGreaterThanOrEqual(
                0,
                $result['mosque_receives'],
                "Property 5 (deducted_from_donation) failed: mosque_receives went negative for amount={$amount}, pct={$pct}"
            );
            $expectedMosque = max(0, $amount - $result['fee_amount']);
            $this->assertEquals(
                $expectedMosque,
                $result['mosque_receives'],
                "Property 5 (deducted_from_donation) failed: mosque_receives should be max(0, {$amount} - {$result['fee_amount']}) = {$expectedMosque}"
            );
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 7: Upsert Idempotence
    public function test_upsert_idempotence(): void
    {
        // Property 7: Calling updateFeeSettings() twice with the same data should yield
        // identical getFeeSettings() results after both calls
        // Validates: Requirements 10.1, 10.3
        foreach ($this->randomSettingsArrays(30) as $settings) {
            $this->service->updateFeeSettings($settings);
            $afterFirst = $this->service->getFeeSettings();

            $this->service->updateFeeSettings($settings);
            $afterSecond = $this->service->getFeeSettings();

            $this->assertEquals(
                $afterFirst,
                $afterSecond,
                'Property 7 (Upsert Idempotence) failed: getFeeSettings() returned different values after identical second call'
            );
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 6: Conservation and Non-Negativity
    public function test_conservation_and_non_negativity(): void
    {
        // Property 6: For any valid input and any mechanism, all outputs must satisfy:
        // fee_amount >= 0, payment_amount >= 1, mosque_receives >= 0,
        // mosque_receives + fee_amount <= payment_amount
        // Validates: Requirements 5.1, 6.1, 7.2, 7.3
        $mechanisms = ['added_to_donor', 'deducted_from_donation'];
        foreach ($this->randomFeeConfigs(50) as [$amount, $pct]) {
            foreach ($mechanisms as $mechanism) {
                $this->service->updateFeeSettings([
                    'fee_percentage' => $pct,
                    'fee_mechanism'  => $mechanism,
                    'fee_active'     => true,
                ]);
                $result = $this->service->calculateFee($amount);
                $this->assertGreaterThanOrEqual(0, $result['fee_amount'],
                    "Property 6 (Conservation) failed: fee_amount < 0 for amount={$amount}, pct={$pct}, mechanism={$mechanism}");
                $this->assertGreaterThanOrEqual(0, $result['mosque_receives'],
                    "Property 6 (Conservation) failed: mosque_receives < 0 for amount={$amount}, pct={$pct}, mechanism={$mechanism}");
                $this->assertGreaterThanOrEqual(1, $result['payment_amount'],
                    "Property 6 (Conservation) failed: payment_amount < 1 for amount={$amount}, pct={$pct}, mechanism={$mechanism}");
                $this->assertLessThanOrEqual(
                    $result['payment_amount'],
                    $result['mosque_receives'] + $result['fee_amount'],
                    "Property 6 (Conservation) failed: mosque_receives + fee_amount > payment_amount for amount={$amount}, pct={$pct}, mechanism={$mechanism}"
                );
            }
        }
    }

    // Feature: day9-platform-settings-fee-config, Property 8: Valid Percentage Persistence Round-Trip
    public function test_valid_percentage_persists_correctly(): void
    {
        // Property 8: For any integer p in [0, 10000], after updateFeeSettings(['fee_percentage' => p, ...])
        // getFeeSettings()['fee_percentage'] must equal p exactly
        // Validates: Requirements 2.1, 10.1, 10.4
        foreach (range(0, 10000, 100) as $pct) {
            $this->service->updateFeeSettings([
                'fee_percentage' => $pct,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => true,
            ]);
            $stored = $this->service->getFeeSettings();
            $this->assertSame(
                $pct,
                $stored['fee_percentage'],
                "Property 8 (Round-Trip) failed: fee_percentage {$pct} was not persisted correctly, got {$stored['fee_percentage']}"
            );
        }
    }

    private function randomAmounts(int $count): array
    {
        $amounts = [];
        for ($i = 0; $i < $count; $i++) {
            $amounts[] = mt_rand(1, 10_000_000);
        }
        return $amounts;
    }

    private function randomFeeConfigs(int $count): array
    {
        $configs = [];
        for ($i = 0; $i < $count; $i++) {
            $configs[] = [mt_rand(1, 10_000_000), mt_rand(0, 10000)];
        }
        return $configs;
    }

    private function randomSettingsArrays(int $count): array
    {
        $mechanisms = ['added_to_donor', 'deducted_from_donation'];
        $arrays = [];
        for ($i = 0; $i < $count; $i++) {
            $arrays[] = [
                'fee_percentage' => mt_rand(0, 10000),
                'fee_mechanism'  => $mechanisms[mt_rand(0, 1)],
                'fee_active'     => (bool) mt_rand(0, 1),
            ];
        }
        return $arrays;
    }
}
