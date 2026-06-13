<?php

// Feature: day11-mosque-suspend-reactivate, Property 4

namespace Tests\Unit\Services;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Validates: Requirements 2.7**
 *
 * Property 4: Guard status — reactivate hanya valid dari suspended.
 *
 * Untuk sembarang mosque_id yang status masjid-nya bukan `suspended`
 * (bisa `pending`, `active`, atau `rejected`), MosqueService::reactivate()
 * harus melempar exception tanpa mengubah status masjid di database.
 */
class MosqueReactivateGuardPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MosqueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->service = app(MosqueService::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Property 4: Guard status — reactivate hanya valid dari suspended
    // Validates: Requirements 2.7
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * **Validates: Requirements 2.7**
     *
     * Property 4: For any mosque whose status is NOT `suspended` (i.e. `pending`,
     * `active`, or `rejected`), calling MosqueService::reactivate() MUST always
     * throw an exception AND the mosque's status in the database MUST remain
     * unchanged after the failed call.
     *
     * Runs 100 iterations, each picking a random non-suspended status.
     */
    public function test_property4_reactivate_throws_exception_for_any_non_suspended_status(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 4
        $nonSuspendedStatuses = [
            MosqueStatus::Pending,
            MosqueStatus::Active,
            MosqueStatus::Rejected,
        ];

        for ($i = 0; $i < 100; $i++) {
            // Pick a random non-suspended status
            $randomStatus = $nonSuspendedStatuses[array_rand($nonSuspendedStatuses)];

            // Create a mosque with the randomly chosen non-suspended status
            $mosque = match ($randomStatus) {
                MosqueStatus::Pending  => Mosque::factory()->pending()->create(),
                MosqueStatus::Active   => Mosque::factory()->create(['status' => MosqueStatus::Active]),
                MosqueStatus::Rejected => Mosque::factory()->rejected()->create(),
            };

            $originalStatus = $mosque->status;
            $actorUserId    = 1; // arbitrary owner ID

            $threw = false;

            try {
                $this->service->reactivate($mosque->id, $actorUserId);
            } catch (\Exception $e) {
                $threw = true;
            }

            // Assert exception was thrown
            $this->assertTrue(
                $threw,
                "Iteration {$i}: reactivate() should throw an exception for mosque with status '{$randomStatus->value}' (mosque_id={$mosque->id})"
            );

            // Assert mosque status is unchanged in the database
            $this->assertDatabaseHas('mosques', [
                'id'     => $mosque->id,
                'status' => $originalStatus->value,
            ]);

            // Clean up mosque to avoid factory uniqueness collisions across iterations
            $mosque->forceDelete();
        }
    }
}
