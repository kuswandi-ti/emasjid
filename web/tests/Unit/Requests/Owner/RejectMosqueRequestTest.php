<?php

namespace Tests\Unit\Requests\Owner;

use App\Enums\MosqueStatus;
use App\Http\Requests\Owner\RejectMosqueRequest;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for RejectMosqueRequest validation rules.
 *
 * Validates: Requirements 3.6, 3.7, 3.8
 */
class RejectMosqueRequestTest extends TestCase
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
     * Run the RejectMosqueRequest rules against the given data.
     */
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new RejectMosqueRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /**
     * Build a valid data payload using a real pending mosque.
     */
    private function validData(int $mosqueId, string $reason = 'Dokumen pendukung tidak lengkap.'): array
    {
        return [
            'mosque_id'        => $mosqueId,
            'rejection_reason' => $reason,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // authorize()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The authorize() method always returns true because route middleware
     * handles authentication and role checking.
     *
     * Validates: Requirements 3.6
     */
    public function test_authorize_returns_true(): void
    {
        $request = new RejectMosqueRequest();

        $this->assertTrue($request->authorize());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – required and exists
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque_id is missing.
     *
     * Validates: Requirements 3.6
     */
    public function test_mosque_id_is_required(): void
    {
        $validator = $this->validate(['rejection_reason' => 'Alasan yang cukup panjang.']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque_id does not correspond to any mosque.
     *
     * Validates: Requirements 3.6
     */
    public function test_mosque_id_must_exist_in_mosques_table(): void
    {
        $validator = $this->validate([
            'mosque_id'        => 99999,
            'rejection_reason' => 'Alasan penolakan yang valid.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // mosque_id – pending status constraint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when mosque exists but is already active (not pending).
     *
     * Validates: Requirements 3.6, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_active(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Alasan penolakan yang valid.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but is already rejected.
     *
     * Validates: Requirements 3.6, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_rejected(): void
    {
        $mosque = Mosque::factory()->rejected()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Alasan penolakan yang valid.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation fails when mosque exists but is suspended (not pending).
     *
     * Validates: Requirements 3.6, 3.8
     */
    public function test_mosque_id_fails_when_mosque_status_is_suspended(): void
    {
        $mosque = Mosque::factory()->suspended()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Alasan penolakan yang valid.',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mosque_id', $validator->errors()->toArray());
    }

    /**
     * Validation passes when mosque_id refers to an existing pending mosque
     * and rejection_reason is long enough.
     *
     * Validates: Requirements 3.6, 3.7, 3.8
     */
    public function test_validation_passes_for_pending_mosque_with_valid_reason(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate($this->validData($mosque->id));

        $this->assertFalse($validator->fails());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // rejection_reason – required
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when rejection_reason is missing entirely.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_is_required(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate(['mosque_id' => $mosque->id]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    /**
     * Validation fails when rejection_reason is null.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_cannot_be_null(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => null,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    /**
     * Validation fails when rejection_reason is an empty string.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_cannot_be_empty_string(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // rejection_reason – minimum length (10 characters)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validation fails when rejection_reason is fewer than 10 characters.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_fails_when_shorter_than_10_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Terlalu', // 7 characters
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    /**
     * Validation fails when rejection_reason is exactly 9 characters (boundary).
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_fails_at_exactly_9_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => '123456789', // exactly 9 characters
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    /**
     * Validation passes when rejection_reason is exactly 10 characters (boundary).
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_passes_at_exactly_10_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => '1234567890', // exactly 10 characters
        ]);

        $this->assertFalse($validator->fails());
    }

    /**
     * Validation passes when rejection_reason is more than 10 characters.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_passes_when_longer_than_10_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate($this->validData($mosque->id, 'Dokumen yang diperlukan tidak lengkap dan tidak valid.'));

        $this->assertFalse($validator->fails());
    }

    /**
     * Validation fails when rejection_reason exceeds 1000 characters.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_fails_when_exceeds_1000_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => str_repeat('a', 1001),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason', $validator->errors()->toArray());
    }

    /**
     * Validation passes when rejection_reason is exactly 1000 characters.
     *
     * Validates: Requirements 3.7
     */
    public function test_rejection_reason_passes_at_exactly_1000_characters(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => str_repeat('a', 1000),
        ]);

        $this->assertFalse($validator->fails());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom error messages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The custom minimum-length error message is returned when rejection_reason
     * is too short.
     *
     * Validates: Requirements 3.7
     */
    public function test_custom_min_error_message_for_rejection_reason(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Pendek',
        ]);

        $errors = $validator->errors()->get('rejection_reason');
        $this->assertNotEmpty($errors);
        // Custom message should mention 10 characters
        $this->assertStringContainsString('10', $errors[0]);
    }

    /**
     * The custom error message is returned when mosque_id refers to a
     * non-pending mosque.
     *
     * Validates: Requirements 3.6, 3.8
     */
    public function test_custom_error_message_for_non_pending_mosque(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);

        $validator = $this->validate([
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Alasan yang panjang cukup.',
        ]);

        $errors = $validator->errors()->get('mosque_id');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('pending', strtolower($errors[0]));
    }
}
