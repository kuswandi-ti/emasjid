<?php

namespace Tests\Unit\Events;

use App\Events\MosqueRejected;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MosqueRejected event.
 *
 * Requirements: 4.2
 */
class MosqueRejectedTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // MosqueRejected — Requirements: 4.2
    // -------------------------------------------------------------------------

    /**
     * Test MosqueRejected event stores the mosque property correctly.
     *
     * Requirements: 4.2
     */
    public function test_event_stores_mosque_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectedByUserId = 1;
        $rejectionReason = 'Dokumen tidak lengkap dan tidak memenuhi persyaratan platform.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, $rejectedByUserId, $rejectionReason, $rejectedAt);

        // Assert
        $this->assertSame($mosque, $event->mosque);
        $this->assertInstanceOf(Mosque::class, $event->mosque);
    }

    /**
     * Test MosqueRejected event stores the rejectedByUserId property correctly.
     *
     * Requirements: 4.2
     */
    public function test_event_stores_rejected_by_user_id_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectedByUserId = 55;
        $rejectionReason = 'Lokasi masjid tidak valid dan tidak dapat diverifikasi.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, $rejectedByUserId, $rejectionReason, $rejectedAt);

        // Assert
        $this->assertEquals(55, $event->rejectedByUserId);
        $this->assertIsInt($event->rejectedByUserId);
    }

    /**
     * Test MosqueRejected event stores the rejectionReason property correctly.
     *
     * Requirements: 4.2
     */
    public function test_event_stores_rejection_reason_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectedByUserId = 1;
        $rejectionReason = 'Informasi yang diberikan tidak akurat dan perlu diverifikasi ulang.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, $rejectedByUserId, $rejectionReason, $rejectedAt);

        // Assert
        $this->assertEquals($rejectionReason, $event->rejectionReason);
        $this->assertIsString($event->rejectionReason);
    }

    /**
     * Test MosqueRejected event stores the rejectedAt timestamp property correctly.
     *
     * Requirements: 4.2
     */
    public function test_event_stores_rejected_at_timestamp_property(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectedByUserId = 1;
        $rejectionReason = 'Alamat masjid tidak sesuai dengan dokumen yang diunggah.';
        $rejectedAt = Carbon::parse('2024-03-20 14:00:00');

        // Act
        $event = new MosqueRejected($mosque, $rejectedByUserId, $rejectionReason, $rejectedAt);

        // Assert
        $this->assertEquals($rejectedAt, $event->rejectedAt);
        $this->assertInstanceOf(Carbon::class, $event->rejectedAt);
    }

    /**
     * Test MosqueRejected event contains all four required properties.
     *
     * Requirements: 4.2
     */
    public function test_event_contains_all_required_properties(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectedByUserId = 3;
        $rejectionReason = 'Dokumen pendukung tidak memadai untuk proses verifikasi masjid.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, $rejectedByUserId, $rejectionReason, $rejectedAt);

        // Assert — all four required properties are present and correctly typed
        $this->assertInstanceOf(Mosque::class, $event->mosque);
        $this->assertIsInt($event->rejectedByUserId);
        $this->assertIsString($event->rejectionReason);
        $this->assertInstanceOf(Carbon::class, $event->rejectedAt);
    }

    /**
     * Test MosqueRejected event preserves the mosque's name for notification purposes.
     *
     * Requirements: 4.2
     */
    public function test_event_mosque_name_is_accessible(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create([
            'name' => 'Masjid Ar-Rahman',
            'status' => MosqueStatus::Rejected,
        ]);
        $rejectionReason = 'Berkas pendaftaran tidak lengkap dan perlu dilengkapi kembali.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, 1, $rejectionReason, $rejectedAt);

        // Assert — mosque name accessible for email notification
        $this->assertEquals('Masjid Ar-Rahman', $event->mosque->name);
    }

    /**
     * Test MosqueRejected event preserves the rejection reason for email notification.
     *
     * Requirements: 4.2
     */
    public function test_event_rejection_reason_is_accessible_for_notification(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $rejectionReason = 'Nomor rekening bank tidak valid dan perlu diverifikasi ulang.';
        $rejectedAt = Carbon::now();

        // Act
        $event = new MosqueRejected($mosque, 1, $rejectionReason, $rejectedAt);

        // Assert — rejection reason should be accessible so listener can include it in email
        $this->assertNotEmpty($event->rejectionReason);
        $this->assertEquals($rejectionReason, $event->rejectionReason);
    }

    /**
     * Test MosqueRejected event can be instantiated with different user IDs.
     *
     * Requirements: 4.2
     */
    public function test_event_accepts_different_user_ids(): void
    {
        // Arrange
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Rejected]);
        $reason = 'Persyaratan pendaftaran tidak terpenuhi secara keseluruhan.';
        $rejectedAt = Carbon::now();

        // Act
        $event1 = new MosqueRejected($mosque, 1, $reason, $rejectedAt);
        $event2 = new MosqueRejected($mosque, 999, $reason, $rejectedAt);

        // Assert
        $this->assertEquals(1, $event1->rejectedByUserId);
        $this->assertEquals(999, $event2->rejectedByUserId);
    }
}
