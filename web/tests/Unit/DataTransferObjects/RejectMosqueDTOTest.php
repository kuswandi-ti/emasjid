<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\RejectMosqueDTO;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RejectMosqueDTOTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test DTO can be created from valid array data
     */
    public function test_creates_dto_from_valid_array_data(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $rejectedAt = Carbon::now();

        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
            'rejected_at' => $rejectedAt->toDateTimeString(),
        ];

        // Act
        $dto = RejectMosqueDTO::fromArray($data);

        // Assert
        $this->assertInstanceOf(RejectMosqueDTO::class, $dto);
        $this->assertEquals($mosque->id, $dto->mosque_id);
        $this->assertEquals($user->id, $dto->rejected_by_user_id);
        $this->assertEquals('This mosque does not meet our requirements.', $dto->rejection_reason);
        $this->assertEquals($rejectedAt->toDateTimeString(), $dto->rejected_at->toDateTimeString());
    }

    /**
     * Test DTO creates with default rejected_at when not provided
     */
    public function test_creates_dto_with_default_rejected_at_when_not_provided(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $beforeCreation = Carbon::now();

        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act
        $dto = RejectMosqueDTO::fromArray($data);
        $afterCreation = Carbon::now();

        // Assert
        $this->assertInstanceOf(Carbon::class, $dto->rejected_at);
        $this->assertTrue($dto->rejected_at->between($beforeCreation, $afterCreation));
    }

    /**
     * Test throws validation exception when mosque_id is missing
     */
    public function test_throws_validation_exception_when_mosque_id_is_missing(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when mosque does not exist
     * Requirements: 6.3
     */
    public function test_throws_validation_exception_when_mosque_does_not_exist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'mosque_id' => 99999, // Non-existent mosque
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when mosque is not pending
     * Requirements: 6.5
     */
    public function test_throws_validation_exception_when_mosque_is_not_pending(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The mosque must have pending status to be rejected.');
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when rejected_by_user_id is missing
     */
    public function test_throws_validation_exception_when_rejected_by_user_id_is_missing(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $data = [
            'mosque_id' => $mosque->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when user does not exist
     */
    public function test_throws_validation_exception_when_user_does_not_exist(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => 99999, // Non-existent user
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when rejection_reason is missing
     */
    public function test_throws_validation_exception_when_rejection_reason_is_missing(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test throws validation exception when rejection_reason is too short (< 10 chars)
     * Requirements: 6.4
     */
    public function test_throws_validation_exception_when_rejection_reason_is_too_short(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'Too short', // Only 9 characters
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Rejection reason must be at least 10 characters.');
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test accepts rejection_reason with exactly 10 characters (minimum boundary)
     * Requirements: 6.4
     */
    public function test_accepts_rejection_reason_with_exactly_10_characters(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => '1234567890', // Exactly 10 characters
        ];

        // Act
        $dto = RejectMosqueDTO::fromArray($data);

        // Assert
        $this->assertInstanceOf(RejectMosqueDTO::class, $dto);
        $this->assertEquals('1234567890', $dto->rejection_reason);
    }

    /**
     * Test DTO can be converted to array
     */
    public function test_converts_dto_to_array(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Pending]);
        $user = User::factory()->create();
        $rejectedAt = Carbon::now();

        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
            'rejected_at' => $rejectedAt->toDateTimeString(),
        ];

        $dto = RejectMosqueDTO::fromArray($data);

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertArrayHasKey('mosque_id', $array);
        $this->assertArrayHasKey('rejected_by_user_id', $array);
        $this->assertArrayHasKey('rejection_reason', $array);
        $this->assertArrayHasKey('rejected_at', $array);
        $this->assertEquals($mosque->id, $array['mosque_id']);
        $this->assertEquals($user->id, $array['rejected_by_user_id']);
        $this->assertEquals('This mosque does not meet our requirements.', $array['rejection_reason']);
    }

    /**
     * Test mosque with suspended status cannot be rejected
     * Requirements: 6.5
     */
    public function test_validates_mosque_with_suspended_status_cannot_be_rejected(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Suspended]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }

    /**
     * Test mosque with rejected status cannot be rejected again
     * Requirements: 6.5
     */
    public function test_validates_mosque_with_rejected_status_cannot_be_rejected_again(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $user = User::factory()->create();
        $data = [
            'mosque_id' => $mosque->id,
            'rejected_by_user_id' => $user->id,
            'rejection_reason' => 'This mosque does not meet our requirements.',
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        RejectMosqueDTO::fromArray($data);
    }
}
