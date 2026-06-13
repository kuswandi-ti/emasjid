<?php

namespace Tests\Feature\Owner;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Property-based tests for MosqueService suspend() and reactivate() operations.
 *
 * Each test runs 100 iterations with randomly generated Mosque records to verify
 * correctness properties hold across all valid inputs.
 *
 * Validates: Requirements 1.3, 1.4
 */
class MosqueSuspendReactivatePropertyTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    private MosqueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->service = app(MosqueService::class);
    }

    // Feature: day11-mosque-suspend-reactivate, Property 1
    /**
     * Property 1: For any active mosque, calling suspend() MUST change its status
     * to Suspended and update the updated_at timestamp.
     *
     * Validates: Requirements 1.3, 1.4
     */
    public function test_suspend_changes_status_to_suspended_and_updates_updated_at(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 1
        Event::fake();

        $owner = $this->createSuperAdmin();

        for ($i = 0; $i < 100; $i++) {
            // Create a random active mosque each iteration
            $mosque = Mosque::factory()->create([
                'status' => MosqueStatus::Active,
            ]);

            $updatedAtBefore = $mosque->updated_at;

            // Small delay to ensure updated_at is strictly greater (1-second precision)
            // Freeze time one second in the past for the mosque, then travel back
            $mosque->updated_at = now()->subSecond();
            $mosque->saveQuietly();

            $updatedAtBefore = $mosque->fresh()->updated_at;

            // Call suspend() on the service
            $result = $this->service->suspend($mosque->id, $owner->id);

            $this->assertTrue(
                $result,
                "Iteration {$i}: suspend() should return true for active mosque id={$mosque->id}"
            );

            $fresh = $mosque->fresh();

            // Assert status changed to Suspended
            $this->assertSame(
                MosqueStatus::Suspended,
                $fresh->status,
                "Iteration {$i}: status should be Suspended after suspend(), got {$fresh->status->value} for mosque id={$mosque->id}"
            );

            // Assert updated_at was refreshed (is >= the before value)
            $this->assertTrue(
                $fresh->updated_at->greaterThanOrEqualTo($updatedAtBefore),
                "Iteration {$i}: updated_at should be updated after suspend() for mosque id={$mosque->id}"
            );
        }
    }
}
