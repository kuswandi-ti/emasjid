<?php

namespace Tests\Feature\Owner;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for Owner Settings (Fee Configuration) access and updates.
 *
 * Validates: Requirements 1.1, 1.2, 1.4, 2.2, 2.4, 3.3, 8.4, 8.5, 10.2, 10.3
 */
class SettingFeatureTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected User $owner;
    protected User $nonOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->owner = $this->createSuperAdmin();
        $this->nonOwner = $this->createUserWithNoRole();
    }

    // ─── Test methods will be added in tasks 6.2–6.10 ────────────────────────

    /**
     * Default values are shown when no settings exist in the database.
     *
     * Validates: Requirement 1.4
     */
    public function test_default_values_shown_when_no_settings_exist(): void
    {
        // Ensure table is empty (RefreshDatabase handles this)
        $this->assertDatabaseCount('platform_settings', 0);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.settings.index'));

        $response->assertOk();
        $response->assertViewHas('settings');
        $settings = $response->viewData('settings');
        $this->assertEquals(0, $settings['fee_percentage'],
            'Default fee_percentage should be 0 when no settings exist');
        $this->assertEquals('added_to_donor', $settings['fee_mechanism'],
            'Default fee_mechanism should be added_to_donor when no settings exist');
        $this->assertFalse($settings['fee_active'],
            'Default fee_active should be false (0) when no settings exist');
    }

    /**
     * Non-super-admin user receives HTTP 403 when accessing the settings page.
     *
     * Validates: Requirement 1.2
     */
    public function test_non_owner_cannot_access_settings_page(): void
    {
        $response = $this->actingAs($this->nonOwner)
            ->get(route('owner.settings.index'));

        $response->assertForbidden();
    }

    /**
     * Settings form is pre-populated with values from the database.
     *
     * Validates: Requirements 1.1, 8.4
     */
    public function test_settings_form_prepopulated_with_stored_values(): void
    {
        // Seed three platform_settings records
        \App\Models\PlatformSetting::create(['key' => 'platform_fee_percentage', 'value' => '500']);
        \App\Models\PlatformSetting::create(['key' => 'platform_fee_mechanism', 'value' => 'deducted_from_donation']);
        \App\Models\PlatformSetting::create(['key' => 'platform_fee_active', 'value' => '1']);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.settings.index'));

        $response->assertOk();
        $response->assertViewHas('settings');
        $settings = $response->viewData('settings');
        $this->assertEquals(500, $settings['fee_percentage']);
        $this->assertEquals('deducted_from_donation', $settings['fee_mechanism']);
        $this->assertTrue($settings['fee_active']);
    }

    /**
     * Success flash message is shown after a valid settings update.
     *
     * Validates: Requirement 8.5
     */
    public function test_success_flash_message_shown_after_update(): void
    {
        $response = $this->actingAs($this->owner)
            ->put(route('owner.settings.update'), [
                'fee_percentage' => 250,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => 1,
            ]);

        $response->assertRedirect(route('owner.settings.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Validation error when fee_percentage is out of range (> 10000).
     *
     * Validates: Requirement 2.2
     */
    public function test_validation_error_for_out_of_range_fee_percentage(): void
    {
        $response = $this->actingAs($this->owner)
            ->put(route('owner.settings.update'), [
                'fee_percentage' => 10001, // out of range: max is 10000
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('fee_percentage');
    }

    /**
     * Validation error when fee_mechanism has an invalid value.
     *
     * Validates: Requirement 3.3
     */
    public function test_validation_error_for_invalid_fee_mechanism(): void
    {
        $response = $this->actingAs($this->owner)
            ->put(route('owner.settings.update'), [
                'fee_percentage' => 250,
                'fee_mechanism'  => 'invalid_value', // not a valid mechanism
                'fee_active'     => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('fee_mechanism');
    }

    /**
     * Upsert creates new records when platform_settings is empty.
     *
     * Validates: Requirement 10.2
     */
    public function test_upsert_creates_record_when_not_exists(): void
    {
        // Ensure table is empty
        $this->assertDatabaseCount('platform_settings', 0);

        $this->actingAs($this->owner)
            ->put(route('owner.settings.update'), [
                'fee_percentage' => 250,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => 1,
            ]);

        // Three new records must be created
        $this->assertDatabaseHas('platform_settings', ['key' => 'platform_fee_percentage', 'value' => '250']);
        $this->assertDatabaseHas('platform_settings', ['key' => 'platform_fee_mechanism', 'value' => 'added_to_donor']);
        $this->assertDatabaseHas('platform_settings', ['key' => 'platform_fee_active', 'value' => '1']);
        $this->assertDatabaseCount('platform_settings', 3);
    }

    /**
     * Non-super-admin user receives HTTP 403 on PUT settings — database unchanged.
     *
     * Validates: Requirement 2.4
     */
    public function test_non_owner_cannot_update_settings(): void
    {
        $response = $this->actingAs($this->nonOwner)
            ->put(route('owner.settings.update'), [
                'fee_percentage' => 250,
                'fee_mechanism'  => 'added_to_donor',
                'fee_active'     => 1,
            ]);

        $response->assertForbidden();

        // Verify database was not modified
        $this->assertDatabaseMissing('platform_settings', ['key' => 'platform_fee_percentage']);
        $this->assertDatabaseMissing('platform_settings', ['key' => 'platform_fee_mechanism']);
        $this->assertDatabaseMissing('platform_settings', ['key' => 'platform_fee_active']);
    }

    /**
     * Upsert does not create duplicate records when called twice with same data.
     *
     * Validates: Requirement 10.3
     */
    public function test_upsert_does_not_create_duplicate_records(): void
    {
        $payload = [
            'fee_percentage' => 250,
            'fee_mechanism'  => 'added_to_donor',
            'fee_active'     => 1,
        ];

        // PUT twice with same data
        $this->actingAs($this->owner)->put(route('owner.settings.update'), $payload);
        $this->actingAs($this->owner)->put(route('owner.settings.update'), $payload);

        // Each key should have exactly 1 record — no duplicates
        $this->assertDatabaseCount('platform_settings', 3);

        $this->assertEquals(
            1,
            \App\Models\PlatformSetting::where('key', 'platform_fee_percentage')->count(),
            'platform_fee_percentage should have exactly 1 record'
        );
        $this->assertEquals(
            1,
            \App\Models\PlatformSetting::where('key', 'platform_fee_mechanism')->count(),
            'platform_fee_mechanism should have exactly 1 record'
        );
        $this->assertEquals(
            1,
            \App\Models\PlatformSetting::where('key', 'platform_fee_active')->count(),
            'platform_fee_active should have exactly 1 record'
        );
    }
}
