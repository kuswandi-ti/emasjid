<?php

namespace Tests\Unit\Requests\Owner;

use App\Enums\MosqueStatus;
use App\Http\Requests\Owner\SuspendMosqueRequest;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for SuspendMosqueRequest validation rules.
 *
 * Validates: Requirements 1.7
 */
class SuspendMosqueRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Run the SuspendMosqueRequest rules against the given data.
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new SuspendMosqueRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // authorize()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The authorize() method always returns true because route middleware
     * handles authentication and role checking.
     *
     * Validates: Requirements 1.7
     */
    public function test_authorize_returns_true(): void
    {
        $request = new SuspendMosqueRequest();

        $this->assertTrue($request->authorize());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – required
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque_id is missing.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_is_required(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque_id is null.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_cannot_be_null(): void
    {
        $validator = $this->validate(['mosque_id' => null]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – exists in mosques table
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque_id does not correspond to any mosque.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_must_exist_in_mosques_table(): void
    {
        $validator = $this->validate(['mosque_id' => 99999]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – active status constraint (PASS case)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation passes when mosque_id refers to an existing active mosque.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_passes_for_existing_active_mosque(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertFalse($validator->fails());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – status guard (FAIL cases)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque exists but its status is 'pending' (not active).
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_fails_when_mosque_status_is_pending(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but its status is already 'suspended'.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_fails_when_mosque_status_is_suspended(): void
    {
        $mosque = Mosque::factory()->suspended()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but its status is 'rejected'.
     *
     * Validates: Requirements 1.7
     */
    public function test_mosque_id_fails_when_mosque_status_is_rejected(): void
    {
        $mosque = Mosque::factory()->rejected()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom error messages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * When mosque_id fails the exists/active check, the custom error message
     * is returned in Indonesian indicating the mosque is not active.
     *
     * Validates: Requirements 1.7
     */
    public function test_custom_error_message_is_returned_when_mosque_not_active(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $errors = $validator->errors()->get('mosque_id');
        $this->assertNotEmpty($errors);

        // Custom message should mention 'aktif' (active in Indonesian)
        $this->assertStringContainsString('aktif', strtolower($errors[0]));
    }
}
