<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\ApproveMosqueDTO;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApproveMosqueDTOTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test DTO can be created with valid data
     */
    public function test_dto_can_be_created_with_valid_data(): void
    {
        // Arrange: Create a pending mosque
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);
        $approver = User::factory()->create();
        $now = Carbon::now();

        // Act: Create DTO
        $dto = ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
            'approved_at' => $now,
        ]);

        // Assert
        $this->assertInstanceOf(ApproveMosqueDTO::class, $dto);
        $this->assertEquals($mosque->id, $dto->mosque_id);
        $this->assertEquals($approver->id, $dto->approved_by_user_id);
        $this->assertEquals($now, $dto->approved_at);
    }

    /**
     * Test DTO can be created from request with automatic timestamp
     */
    public function test_dto_can_be_created_from_request(): void
    {
        // Arrange: Create a pending mosque
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);
        $approver = User::factory()->create();

        // Act: Create DTO from request
        $dto = ApproveMosqueDTO::fromRequest($mosque->id, $approver->id);

        // Assert
        $this->assertInstanceOf(ApproveMosqueDTO::class, $dto);
        $this->assertEquals($mosque->id, $dto->mosque_id);
        $this->assertEquals($approver->id, $dto->approved_by_user_id);
        $this->assertInstanceOf(Carbon::class, $dto->approved_at);
    }

    /**
     * Test DTO validates mosque_id is required
     */
    public function test_dto_validates_mosque_id_is_required(): void
    {
        // Arrange
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO validates mosque_id must be integer
     */
    public function test_dto_validates_mosque_id_must_be_integer(): void
    {
        // Arrange
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => 'invalid',
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO validates mosque must exist
     */
    public function test_dto_validates_mosque_must_exist(): void
    {
        // Arrange
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => 99999, // Non-existent ID
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO validates mosque must have pending status
     * Requirements 6.1, 6.3
     */
    public function test_dto_validates_mosque_must_be_pending(): void
    {
        // Arrange: Create an active mosque (not pending)
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Active,
        ]);
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only pending mosques can be approved');
        
        ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO rejects suspended mosque
     */
    public function test_dto_rejects_suspended_mosque(): void
    {
        // Arrange: Create a suspended mosque
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Suspended,
        ]);
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO rejects rejected mosque
     */
    public function test_dto_rejects_rejected_mosque(): void
    {
        // Arrange: Create a rejected mosque
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Rejected,
        ]);
        $approver = User::factory()->create();

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
        ]);
    }

    /**
     * Test DTO validates approved_by_user_id is required
     */
    public function test_dto_validates_approved_by_user_id_is_required(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
        ]);
    }

    /**
     * Test DTO validates approved_by_user_id must be integer
     */
    public function test_dto_validates_approved_by_user_id_must_be_integer(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => 'invalid',
        ]);
    }

    /**
     * Test DTO uses current timestamp when approved_at not provided
     */
    public function test_dto_uses_current_timestamp_when_not_provided(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);
        $approver = User::factory()->create();
        $before = Carbon::now();

        // Act
        $dto = ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
        ]);

        $after = Carbon::now();

        // Assert
        $this->assertGreaterThanOrEqual($before, $dto->approved_at);
        $this->assertLessThanOrEqual($after, $dto->approved_at);
    }

    /**
     * Test DTO can be converted to array
     */
    public function test_dto_can_be_converted_to_array(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);
        $approver = User::factory()->create();
        $now = Carbon::now();

        $dto = ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
            'approved_at' => $now,
        ]);

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals($mosque->id, $array['mosque_id']);
        $this->assertEquals($approver->id, $array['approved_by_user_id']);
        $this->assertEquals($now, $array['approved_at']);
    }

    /**
     * Test DTO properties are readonly
     */
    public function test_dto_properties_are_readonly(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'status' => MosqueStatus::Pending,
        ]);
        $approver = User::factory()->create();

        $dto = ApproveMosqueDTO::fromArray([
            'mosque_id' => $mosque->id,
            'approved_by_user_id' => $approver->id,
        ]);

        // Act & Assert: Trying to modify readonly property should throw error
        $this->expectException(\Error::class);
        $dto->mosque_id = 999;
    }
}
