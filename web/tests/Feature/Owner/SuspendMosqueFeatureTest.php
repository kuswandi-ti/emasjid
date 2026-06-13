<?php

namespace Tests\Feature\Owner;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for Owner\MosqueController::suspend() — HTTP layer.
 *
 * Covers:
 *  - Validation failure (redirect + errors) when mosque status is not `active`
 *  - HTTP 403 when authenticated user is not a super-admin
 *  - Successful suspend: redirect with flash `success` and DB status = `suspended`
 *  - Error handling: mocked service throws, flash `error` present, DB status unchanged
 *
 * Validates: Requirements 1.7, 1.8, 1.9
 */
class SuspendMosqueFeatureTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 1.7 – Validation failure when mosque status is not active
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST suspend on a pending mosque → redirect back with mosque_id validation error.
     * SuspendMosqueRequest requires mosque_id to exist with status = active.
     *
     * Validates: Requirement 1.7
     */
    public function test_suspend_redirects_with_validation_error_when_mosque_is_pending(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // FormRequest validation fails → redirect back with errors
        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        // Status must remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Pending->value,
        ]);
    }

    /**
     * POST suspend on an already-suspended mosque → redirect back with mosque_id validation error.
     *
     * Validates: Requirement 1.7
     */
    public function test_suspend_redirects_with_validation_error_when_mosque_is_already_suspended(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Suspended->value,
        ]);
    }

    /**
     * POST suspend on a rejected mosque → redirect back with mosque_id validation error.
     *
     * Validates: Requirement 1.7
     */
    public function test_suspend_redirects_with_validation_error_when_mosque_is_rejected(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->rejected()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
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
     * The validation error message for a non-active mosque matches the spec text.
     *
     * Validates: Requirement 1.7
     */
    public function test_suspend_validation_error_message_is_correct(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->suspended()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'mosque_id' => 'Masjid tidak dapat ditangguhkan karena statusnya bukan aktif.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 1.8 – 403 when user is not super-admin
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST suspend by a mosque-admin user → 403.
     *
     * Validates: Requirement 1.8
     */
    public function test_suspend_returns_403_for_mosque_admin(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->create(); // active by default

        $response = $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();

        // Status must remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * POST suspend by a user with no role → 403.
     *
     * Validates: Requirement 1.8
     */
    public function test_suspend_returns_403_for_roleless_user(): void
    {
        $user   = $this->createUserWithNoRole();
        $mosque = Mosque::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * POST suspend by an unauthenticated user → redirect to login.
     *
     * Validates: Requirement 1.8
     */
    public function test_suspend_redirects_unauthenticated_user_to_login(): void
    {
        $mosque = Mosque::factory()->create();

        $response = $this->post(route('owner.mosques.suspend', $mosque->id), [
            'mosque_id' => $mosque->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirements 1.3, 1.5 – Successful suspend flow
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST suspend with a valid active mosque by super-admin → redirect to detail
     * page with flash `success`, and DB status becomes `suspended`.
     *
     * Validates: Requirements 1.3, 1.5
     */
    public function test_suspend_succeeds_for_active_mosque_by_super_admin(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // active by default

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // Assert redirect to mosque detail page with flash success
        $response->assertRedirect(route('owner.mosques.show', $mosque->id));
        $response->assertSessionHas('success', 'Masjid berhasil ditangguhkan.');

        // Assert DB status changed to suspended
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Suspended->value,
        ]);
    }

    /**
     * After a successful suspend the session must NOT contain an `error` key.
     *
     * Validates: Requirement 1.5
     */
    public function test_suspend_success_does_not_set_error_flash(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertSessionMissing('error');
    }

    /**
     * Attempting to suspend an active mosque a second time is blocked after the
     * first suspension: the mosque is now `suspended`, so validation rejects it.
     *
     * Validates: Requirements 1.7 (idempotency guard)
     */
    public function test_second_suspend_on_already_suspended_mosque_is_rejected(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // active

        // First suspension — succeeds
        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ])
            ->assertRedirect(route('owner.mosques.show', $mosque->id));

        $this->assertSame(MosqueStatus::Suspended, $mosque->fresh()->status);

        // Second attempt — mosque is now suspended, so validation must reject it
        $secondResponse = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors(['mosque_id']);

        // Status must remain suspended
        $this->assertSame(MosqueStatus::Suspended, $mosque->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 1.9 – Error handling / rollback
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * When MosqueService::suspend() throws, the controller catches the exception,
     * redirects back with flash `error`, and the mosque status remains unchanged.
     *
     * Validates: Requirement 1.9
     */
    public function test_suspend_redirects_with_error_flash_when_service_throws(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // active — passes FormRequest validation

        // Mock the service to throw a generic exception (simulates DB / runtime error)
        $this->mock(MosqueService::class, function ($mock) use ($mosque) {
            $mock->shouldReceive('suspend')
                ->once()
                ->with($mosque->id, \Mockery::any())
                ->andThrow(new \Exception('Database error simulated'));
        });

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // Should redirect back (to the previous page) with error flash
        $response->assertRedirect(route('owner.mosques.show', $mosque->id));
        $response->assertSessionHas('error');

        // DB status must remain active — no partial change
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * When MosqueService::suspend() throws, the session must NOT contain `success`.
     *
     * Validates: Requirement 1.9
     */
    public function test_suspend_error_does_not_set_success_flash(): void
    {
        Queue::fake();
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create(); // active

        $this->mock(MosqueService::class, function ($mock) use ($mosque) {
            $mock->shouldReceive('suspend')
                ->once()
                ->with($mosque->id, \Mockery::any())
                ->andThrow(new \Exception('Simulated failure'));
        });

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.suspend', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertSessionMissing('success');
    }
}
