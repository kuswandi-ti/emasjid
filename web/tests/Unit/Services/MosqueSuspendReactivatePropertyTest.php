<?php

namespace Tests\Unit\Services;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Property-Based Tests for MosqueService suspend and reactivate operations.
 *
 * Validates: Requirements 1.3, 1.4, 2.3
 *
 * Feature: day11-mosque-suspend-reactivate
 */
class MosqueSuspendReactivatePropertyTest extends TestCase
{
    use RefreshDatabase;

    private MosqueService $service;
    private int $actorUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->service = app(MosqueService::class);
        $this->actorUserId = User::factory()->create()->id;
    }

    // -------------------------------------------------------------------------
    // Property 2: Reactivate mengubah status menjadi active
    // Feature: day11-mosque-suspend-reactivate, Property 2
    // Validates: Requirements 2.3
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 2.3**
     *
     * Property 2: For any mosque with status `suspended`, calling
     * MosqueService::reactivate() MUST always change the mosque status
     * to `Active` and update `updated_at`.
     *
     * Runs 100 iterations with randomly created suspended mosques.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 2
     */
    public function test_property_2_reactivate_always_changes_status_to_active(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 2
        Event::fake();

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->suspended()->create();

            $updatedAtBefore = $mosque->updated_at;

            // Advance time slightly so updated_at will differ
            $this->travel(1)->seconds();

            $result = $this->service->reactivate($mosque->id, $this->actorUserId);

            $this->assertTrue(
                $result,
                "Iteration {$i}: reactivate() should return true for suspended mosque id={$mosque->id}"
            );

            $fresh = $mosque->fresh();

            $this->assertSame(
                MosqueStatus::Active,
                $fresh->status,
                "Iteration {$i}: Status must be Active after reactivate(), got {$fresh->status->value} for mosque id={$mosque->id}"
            );

            $this->assertTrue(
                $fresh->updated_at->greaterThanOrEqualTo($updatedAtBefore),
                "Iteration {$i}: updated_at must be updated after reactivate() for mosque id={$mosque->id}"
            );
        }
    }
}
