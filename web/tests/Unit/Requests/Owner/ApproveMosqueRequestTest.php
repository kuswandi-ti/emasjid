<?php

namespace Tests\Unit\Requests\Owner;

use App\Enums\MosqueStatus;
use App\Http\Requests\Owner\ApproveMosqueRequest;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for ApproveMosqueRequest validation rules.
 *
 * Validates: Requirements 3.3, 3.8
 */
class ApproveMosqueRequestTest extends TestCase
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
     * Run the ApproveMosqueRequest rules against the given data.
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new ApproveMosqueRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // authorize()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The authorize() method always returns true because the route middleware
     * handles authentication and role checking.
     *
     * Validates: Requirements 3.3
     */
    public function test_authorize_returns_true(): void
    {
        $request = new ApproveMosqueRequest();

        $this->assertTrue($request->authorize());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – required
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque_id is missing.
     *
     * Validates: Requirements 3.3
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
     * Validates: Requirements 3.3
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
     * Validates: Requirements 3.3
     */
    public function test_mosque_id_must_exist_in_mosques_table(): void
    {
        $validator = $this->validate(['mosque_id' => 99999]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation passes when mosque_id refers to an existing pending mosque.
     *
     * Validates: Requirements 3.3
     */
    public function test_mosque_id_passes_for_existing_pending_mosque(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertFalse($validator->fails());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – pending status constraint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque exists but its status is 'active' (not pending).
     *
     * Validates: Requirements 3.3, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_active(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but its status is 'rejected'.
     *
     * Validates: Requirements 3.3, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_rejected(): void
    {
        $mosque = Mosque::factory()->rejected()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but its status is 'suspended'.
     *
     * Validates: Requirements 3.3, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_suspended(): void
    {
        $mosque = Mosque::factory()->suspended()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom error messages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * When mosque_id fails the exists/pending check, the custom error message
     * is returned (not the default Laravel message).
     *
     * Validates: Requirements 3.3
     */
    public function test_custom_error_message_is_returned_when_mosque_not_pending(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $errors = $validator->errors()->get('mosque_id');
        $this->assertNotEmpty($errors);

        // The custom message should indicate the mosque is not in pending/awaiting status.
        // The message is in Indonesian; it uses "menunggu" (awaiting) or "pending".
        $lowerMessage = strtolower($errors[0]);
        $this->assertTrue(
            str_contains($lowerMessage, 'pending') || str_contains($lowerMessage, 'menunggu'),
            "Expected error message to mention pending/menunggu status, got: {$errors[0]}"
        );
    }
}
