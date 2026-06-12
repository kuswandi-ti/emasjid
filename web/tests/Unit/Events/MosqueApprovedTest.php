<?php

namespace Tests\Unit\Events;

use App\Events\MosqueApproved;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MosqueApproved event.
 *
 * Requirements: 4.1
 */
class MosqueApprovedTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // MosqueApproved — Requirements: 4.1
    // -------------------------------------------------------------------------

    /**
     * Test MosqueApproved event stores the mosque property correctly.
     *
     * Requirements: 4.1
     */
    public function test_event_stores_mosque_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $approvedByUserId = 1;
        $approvedAt = Carbon::now();

        // Act
        $event = new MosqueApproved($mosque, $approvedByUserId, $approvedAt);

        // Assert
        $this->assertSame($mosque, $event->mosque);
        $this->assertInstanceOf(Mosque::class, $event->mosque);
    }

    /**
     * Test MosqueApproved event stores the approvedByUserId property correctly.
     *
     * Requirements: 4.1
     */
    public function test_event_stores_approved_by_user_id_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $approvedByUserId = 42;
        $approvedAt = Carbon::now();

        // Act
        $event = new MosqueApproved($mosque, $approvedByUserId, $approvedAt);

        // Assert
        $this->assertEquals(42, $event->approvedByUserId);
        $this->assertIsInt($event->approvedByUserId);
    }

    /**
     * Test MosqueApproved event stores the approvedAt timestamp property correctly.
     *
     * Requirements: 4.1
     */
    public function test_event_stores_approved_at_timestamp_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $approvedByUserId = 1;
        $approvedAt = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $event = new MosqueApproved($mosque, $approvedByUserId, $approvedAt);

        // Assert
        $this->assertEquals($approvedAt, $event->approvedAt);
        $this->assertInstanceOf(Carbon::class, $event->approvedAt);
    }

    /**
     * Test MosqueApproved event contains all three required properties.
     *
     * Requirements: 4.1
     */
    public function test_event_contains_all_required_properties(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $approvedByUserId = 7;
        $approvedAt = Carbon::now();

        // Act
        $event = new MosqueApproved($mosque, $approvedByUserId, $approvedAt);

        // Assert — all three required properties are present and correctly typed
        $this->assertInstanceOf(Mosque::class, $event->mosque);
        $this->assertIsInt($event->approvedByUserId);
        $this->assertInstanceOf(Carbon::class, $event->approvedAt);
    }

    /**
     * Test MosqueApproved event preserves the mosque's name for notification purposes.
     *
     * Requirements: 4.1
     */
    public function test_event_mosque_name_is_accessible(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'name' => 'Masjid Al-Ikhlas',
            'status' => MosqueStatus::Active,
        ]);
        $approvedAt = Carbon::now();

        // Act
        $event = new MosqueApproved($mosque, 1, $approvedAt);

        // Assert — mosque name accessible for email notification
        $this->assertEquals('Masjid Al-Ikhlas', $event->mosque->name);
    }

    /**
     * Test MosqueApproved event can be instantiated with different user IDs.
     *
     * Requirements: 4.1
     */
    public function test_event_accepts_different_user_ids(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $approvedAt = Carbon::now();

        // Act
        $event1 = new MosqueApproved($mosque, 1, $approvedAt);
        $event2 = new MosqueApproved($mosque, 999, $approvedAt);

        // Assert
        $this->assertEquals(1, $event1->approvedByUserId);
        $this->assertEquals(999, $event2->approvedByUserId);
    }
}
