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
}
