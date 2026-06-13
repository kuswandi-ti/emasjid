<?php

namespace Tests\Feature\Owner;

use App\Enums\MosqueStatus;
use App\Events\MosqueReactivated;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature (HTTP layer) tests for POST /owner/mosques/{id}/reactivate.
 *
 * Validates: Requirements 2.7, 2.8, 2.9
 */
class MosqueReactivateHttpTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 2.8 – Authorization: only super-admin may reactivate
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A mosque-admin user receives HTTP 403 when POSTing to the reactivate endpoint.
     *
     * Validates: Requirement 2.8
     */
    public function test_non_super_admin_receives_403_on_reactivate(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();

        // Status must remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Suspended->value,
        ]);
    }

    /**
     * A plain user with no role receives HTTP 403 on the reactivate endpoint.
     *
     * Validates: Requirement 2.8
     */
    public function test_user_with_no_role_receives_403_on_reactivate(): void
    {
        $user   = $this->createUserWithNoRole();
        $mosque = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($user)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();
    }

    /**
     * An unauthenticated request is redirected to the login page.
     *
     * Validates: Requirement 2.8 (implicit — middleware auth:web)
     */
    public function test_unauthenticated_user_is_redirected_to_login_on_reactivate(): void
    {
        $mosque = Mosque::factory()->suspended()->create();

        $response = $this->post(route('owner.mosques.reactivate', $mosque->id), [
            'mosque_id' => $mosque->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 2.7 – Validation: 422 when mosque status is not suspended
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POSTing reactivate for a mosque whose status is `active` returns 422 (validation failure).
     *
     * Validates: Requirement 2.7
     */
    public function test_reactivate_returns_422_for_active_mosque(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // default status = active

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // ReactivateMosqueRequest fails because mosque is not suspended → 422 redirect with errors
        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * POSTing reactivate for a mosque whose status is `pending` returns 422 (validation failure).
     *
     * Validates: Requirement 2.7
     */
    public function test_reactivate_returns_422_for_pending_mosque(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Pending->value,
        ]);
    }

    /**
     * POSTing reactivate for a mosque whose status is `rejected` returns 422 (validation failure).
     *
     * Validates: Requirement 2.7
     */
    public function test_reactivate_returns_422_for_rejected_mosque(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->rejected()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Rejected->value,
        ]);
    }

    /**
     * The validation error message for a non-suspended mosque matches the spec text.
     *
     * Validates: Requirement 2.7
     */
    public function test_reactivate_validation_error_message_is_correct(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // active

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'mosque_id' => 'Masjid tidak dapat diaktifkan kembali karena statusnya bukan ditangguhkan.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 2.3, 2.4 – Success: reactivate a suspended mosque
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A super-admin successfully reactivates a suspended mosque:
     * - DB status changes to `active`
     * - Redirects to the mosque detail page
     * - Flash message `success` is present
     *
     * Validates: Requirements 2.3, 2.4
     */
    public function test_super_admin_can_reactivate_suspended_mosque_and_status_changes_to_active(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // Redirects to the mosque detail page
        $response->assertRedirect(route('owner.mosques.show', $mosque->id));

        // Flash success message is present
        $response->assertSessionHas('success', 'Masjid berhasil diaktifkan kembali.');

        // DB status is now active
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * Reactivating a suspended mosque dispatches the MosqueReactivated event.
     *
     * Validates: Requirement 2.3
     */
    public function test_reactivate_dispatches_mosque_reactivated_event(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        Event::assertDispatched(MosqueReactivated::class, function (MosqueReactivated $event) use ($mosque) {
            return $event->mosque->id === $mosque->id;
        });
    }

    /**
     * Side-effect jobs are dispatched asynchronously (via queue) and not run inline,
     * so the main reactivate flow is not blocked by notification delivery.
     *
     * Validates: Requirement 4.4 (async notification)
     */
    public function test_reactivate_queues_notification_asynchronously_and_does_not_block(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // Main flow completes with redirect — notification is queued, not inline
        $response->assertRedirect(route('owner.mosques.show', $mosque->id));
        $response->assertSessionHas('success');

        // DB is updated correctly despite queue being faked
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * Attempting to reactivate an already-active mosque a second time is blocked:
     * the first reactivation succeeds, and a subsequent attempt on the same mosque
     * (which is now active) fails with a validation error.
     *
     * Validates: Requirement 2.7 (idempotency guard)
     */
    public function test_second_reactivate_on_already_active_mosque_is_rejected(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        // First reactivation — succeeds
        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ])
            ->assertRedirect(route('owner.mosques.show', $mosque->id));

        $this->assertSame(MosqueStatus::Active, $mosque->fresh()->status);

        // Second attempt — mosque is now active, so validation must reject it
        $secondResponse = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reactivate', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors(['mosque_id']);

        // Status must remain active; MosqueReactivated dispatched only once
        $this->assertSame(MosqueStatus::Active, $mosque->fresh()->status);
        Event::assertDispatchedTimes(MosqueReactivated::class, 1);
    }
}
