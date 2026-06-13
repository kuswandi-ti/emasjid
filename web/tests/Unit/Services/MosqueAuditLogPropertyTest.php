<?php

// Feature: day11-mosque-suspend-reactivate, Property 6
// Feature: day11-mosque-suspend-reactivate, Property 7

namespace Tests\Unit\Services;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Property-Based Tests: Audit Log untuk Suspend dan Reactivate Masjid.
 *
 * **Validates: Requirements 8.1, 8.2**
 *
 * Feature: day11-mosque-suspend-reactivate, Property 6 & 7
 */
class MosqueAuditLogPropertyTest extends TestCase
{
    use RefreshDatabase;

    private MosqueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->service = app(MosqueService::class);
    }

    // -------------------------------------------------------------------------
    // Property 6: Audit log tercatat pada setiap suspend berhasil
    // Validates: Requirements 8.1
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 8.1**
     *
     * Property 6: For any successful MosqueService::suspend() operation,
     * Log::info() MUST be called with the exact format:
     * "Mosque suspended: mosque_id={id}, mosque_name={name}, by_user_id={userId}"
     *
     * The log must contain the correct mosque_id, mosque_name, and by_user_id
     * for every suspend operation across 100 random iterations.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 6
     */
    public function test_property6_audit_log_is_recorded_on_every_successful_suspend(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 6
        Event::fake(); // Suppress FCM side-effects (MosqueSuspended listeners)
        Log::spy();    // Spy on all log calls for this test

        $expectedMessages = [];

        for ($i = 0; $i < 100; $i++) {
            // Mosque::factory() defaults to MosqueStatus::Active — no active() state needed
            $mosque = Mosque::factory()->create();
            $userId = User::factory()->create()->id;

            $expectedMessages[] = "Mosque suspended: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}";

            $result = $this->service->suspend($mosque->id, $userId);

            $this->assertTrue(
                $result,
                "Iteration {$i}: suspend() must return true for mosque id={$mosque->id}"
            );
        }

        // Assert Log::info was called exactly 100 times (once per successful suspend)
        Log::shouldHaveReceived('info')->times(100);

        // Assert each expected audit log message was recorded
        foreach ($expectedMessages as $message) {
            Log::shouldHaveReceived('info')->with($message);
        }
    }

    // -------------------------------------------------------------------------
    // Property 7: Audit log tercatat pada setiap reactivate berhasil
    // Validates: Requirements 8.2
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 8.2**
     *
     * Property 7: For any successful MosqueService::reactivate() operation,
     * Log::info() MUST be called with the exact format:
     * "Mosque reactivated: mosque_id={id}, mosque_name={name}, by_user_id={userId}"
     *
     * The log must contain the correct mosque_id, mosque_name, and by_user_id
     * for every reactivate operation across 100 random iterations.
     *
     * // Feature: day11-mosque-suspend-reactivate, Property 7
     */
    public function test_property7_audit_log_is_recorded_on_every_successful_reactivate(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 7
        Event::fake(); // Suppress FCM side-effects (MosqueReactivated listeners)
        Log::spy();    // Spy on all log calls for this test

        $expectedMessages = [];

        for ($i = 0; $i < 100; $i++) {
            $mosque = Mosque::factory()->suspended()->create();
            $userId = User::factory()->create()->id;

            $expectedMessages[] = "Mosque reactivated: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}";

            $result = $this->service->reactivate($mosque->id, $userId);

            $this->assertTrue(
                $result,
                "Iteration {$i}: reactivate() must return true for mosque id={$mosque->id}"
            );
        }

        // Assert Log::info was called exactly 100 times (once per successful reactivate)
        Log::shouldHaveReceived('info')->times(100);

        // Assert each expected audit log message was recorded
        foreach ($expectedMessages as $message) {
            Log::shouldHaveReceived('info')->with($message);
        }
    }
}
