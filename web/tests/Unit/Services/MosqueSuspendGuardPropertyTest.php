<?php

// Feature: day11-mosque-suspend-reactivate, Property 3

namespace Tests\Unit\Services;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * **Validates: Requirements 1.7**
 *
 * Property 3: Guard status — suspend hanya valid dari active.
 *
 * Untuk sembarang mosque_id yang status masjid-nya bukan `active`
 * (bisa `pending`, `suspended`, atau `rejected`), MosqueService::suspend()
 * harus melempar exception tanpa mengubah status masjid di database.
 */
class MosqueSuspendGuardPropertyTest extends TestCase
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
    // Property 3: Guard status — suspend hanya valid dari active
    // Validates: Requirements 1.7
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * **Validates: Requirements 1.7**
     *
     * Property 3: For any mosque whose status is NOT `active` (i.e. `pending`,
     * `suspended`, or `rejected`), calling MosqueService::suspend() MUST always
     * throw an exception AND the mosque's status in the database MUST remain
     * unchanged after the failed call.
     *
     * Runs 100 iterations, each picking a random non-active status.
     */
    public function test_property3_suspend_throws_exception_for_any_non_active_status(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 3
        $nonActiveStatuses = [
            MosqueStatus::Pending,
            MosqueStatus::Suspended,
            MosqueStatus::Rejected,
        ];

        for ($i = 0; $i < 100; $i++) {
            // Pick a random non-active status
            $randomStatus = $nonActiveStatuses[array_rand($nonActiveStatuses)];

            // Create a mosque with the randomly chosen non-active status
            $mosque = match ($randomStatus) {
                MosqueStatus::Pending   => Mosque::factory()->pending()->create(),
                MosqueStatus::Suspended => Mosque::factory()->suspended()->create(),
                MosqueStatus::Rejected  => Mosque::factory()->rejected()->create(),
            };

            $originalStatus = $mosque->status;
            $actorUserId    = 1; // arbitrary owner ID

            $threw = false;

            try {
                $this->service->suspend($mosque->id, $actorUserId);
            } catch (\Exception $e) {
                $threw = true;
            }

            // Assert exception was thrown
            $this->assertTrue(
                $threw,
                "Iteration {$i}: suspend() should throw an exception for mosque with status '{$randomStatus->value}' (mosque_id={$mosque->id})"
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
