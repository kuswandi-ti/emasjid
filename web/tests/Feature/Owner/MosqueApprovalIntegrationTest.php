<?php

namespace Tests\Feature\Owner;

use App\Enums\MosqueStatus;
use App\Events\MosqueApproved;
use App\Events\MosqueRejected;
use App\Listeners\SendMosqueApprovedNotification;
use App\Listeners\SendMosqueRejectedNotification;
use App\Models\Mosque;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Integration tests for mosque approval/rejection end-to-end flows.
 *
 * These tests exercise the full HTTP stack — controller → service → repository →
 * database — without mocking the service layer, verifying all side-effects:
 * database state, events, and queued email jobs.
 *
 * Validates: Requirements 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 1.10,
 *            2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 10.1, 10.2, 10.4
 */
class MosqueApprovalIntegrationTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 16.1 – Approval flow end-to-end
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Approving a pending mosque sets status to ACTIVE and records approved_at / approved_by.
     *
     * Validates: Requirements 1.2, 1.3, 1.4, 1.7, 1.8, 1.9
     */
    public function test_approval_sets_status_to_active_and_records_audit_fields(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $mosque->refresh();

        $this->assertSame(MosqueStatus::Active, $mosque->status);
        $this->assertNotNull($mosque->approved_at);
        $this->assertSame($superAdmin->id, $mosque->approved_by);
    }

    /**
     * Approving a pending mosque generates a 6-character unique invitation code.
     *
     * Validates: Requirements 1.5, 1.6
     */
    public function test_approval_generates_unique_6_char_invitation_code(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $mosque->refresh();

        $this->assertNotNull($mosque->invitation_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $mosque->invitation_code);
    }

    /**
     * Two separately approved mosques get distinct invitation codes.
     *
     * Validates: Requirement 1.6
     */
    public function test_approval_invitation_codes_are_unique_across_mosques(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosqueA    = Mosque::factory()->pending()->create();
        $mosqueB    = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosqueA->id), ['mosque_id' => $mosqueA->id]);

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosqueB->id), ['mosque_id' => $mosqueB->id]);

        $this->assertNotSame($mosqueA->fresh()->invitation_code, $mosqueB->fresh()->invitation_code);
    }

    /**
     * Approval redirects to the mosque detail page with a success flash message.
     *
     * Validates: Requirements 1.10, 1.11
     */
    public function test_approval_redirects_to_detail_page_with_success_flash(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect(route('owner.mosques.show', $mosque->id));
        $response->assertSessionHas('success');
    }

    /**
     * Approval dispatches the MosqueApproved event.
     *
     * Validates: Requirement 1.10
     */
    public function test_approval_dispatches_mosque_approved_event(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        Event::assertDispatched(MosqueApproved::class, function (MosqueApproved $event) use ($mosque) {
            return $event->mosque->id === $mosque->id;
        });
    }

    /**
     * Approval queues the SendMosqueApprovedNotification listener job (email job queued).
     *
     * Because the listener implements ShouldQueue, dispatching MosqueApproved causes
     * Laravel to push a CallQueuedListener job wrapping the listener class onto the queue
     * rather than executing it synchronously.
     *
     * Validates: Requirements 4.3, 4.5, 4.7 (async email)
     */
    public function test_approval_queues_email_notification_job(): void
    {
        Queue::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // ShouldQueue listeners are pushed as CallQueuedListener jobs wrapping the class
        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === SendMosqueApprovedNotification::class;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 16.2 – Rejection flow end-to-end
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Rejecting a pending mosque sets status to REJECTED and persists audit fields.
     *
     * Validates: Requirements 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
     */
    public function test_rejection_sets_status_to_rejected_and_records_audit_fields(): void
    {
        Event::fake();

        $superAdmin      = $this->createSuperAdmin();
        $mosque          = Mosque::factory()->pending()->create();
        $rejectionReason = 'Dokumen tidak lengkap dan tidak memenuhi persyaratan platform.';

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => $rejectionReason,
            ]);

        $mosque->refresh();

        $this->assertSame(MosqueStatus::Rejected, $mosque->status);
        $this->assertSame($rejectionReason, $mosque->rejection_reason);
        $this->assertNotNull($mosque->rejected_at);
        $this->assertSame($superAdmin->id, $mosque->rejected_by);
    }

    /**
     * Rejection redirects to the pending mosque list with a success flash message.
     *
     * Validates: Requirements 2.10, 2.11
     */
    public function test_rejection_redirects_to_pending_list_with_success_flash(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Informasi yang disampaikan tidak mencukupi verifikasi.',
            ]);

        $response->assertRedirect(route('owner.mosques.pending'));
        $response->assertSessionHas('success');
    }

    /**
     * Rejection dispatches the MosqueRejected event.
     *
     * Validates: Requirement 2.10
     */
    public function test_rejection_dispatches_mosque_rejected_event(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Dokumen tidak lengkap untuk proses review platform.',
            ]);

        Event::assertDispatched(MosqueRejected::class, function (MosqueRejected $event) use ($mosque) {
            return $event->mosque->id === $mosque->id;
        });
    }

    /**
     * Rejection queues the SendMosqueRejectedNotification listener job (email job queued).
     *
     * Validates: Requirements 4.4, 4.7 (async email)
     */
    public function test_rejection_queues_email_notification_job(): void
    {
        Queue::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Informasi tidak mencukupi untuk keperluan verifikasi.',
            ]);

        // ShouldQueue listeners are pushed as CallQueuedListener jobs wrapping the class
        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            return $job->class === SendMosqueRejectedNotification::class;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 16.3 – Authorization enforcement
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A non-super-admin user receives HTTP 403 on both approve and reject endpoints.
     *
     * Validates: Requirements 10.1, 10.2
     */
    public function test_non_super_admin_is_forbidden_from_both_approve_and_reject(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->pending()->create();

        // POST to approve → 403
        $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ])
            ->assertForbidden();

        // POST to reject → 403
        $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan penolakan yang cukup panjang untuk validasi.',
            ])
            ->assertForbidden();

        // Mosque status must remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Pending->value,
        ]);
    }

    /**
     * A plain authenticated user (no role) also receives HTTP 403 on both endpoints.
     *
     * Validates: Requirement 10.2
     */
    public function test_roleless_user_is_forbidden_from_both_approve_and_reject(): void
    {
        $user   = $this->createUserWithNoRole();
        $mosque = Mosque::factory()->pending()->create();

        $this->actingAs($user)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan penolakan yang cukup panjang untuk validasi.',
            ])
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Task 16.4 – Concurrent approval prevention
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Only the first approval of a pending mosque succeeds; a second approval
     * attempt on the same mosque is rejected by validation (mosque is no longer
     * pending), ensuring database consistency under concurrent-like requests.
     *
     * We simulate concurrency by running two approval requests sequentially
     * against the same mosque without refreshing state between them. The second
     * request arrives after the first has already committed the status change,
     * which is the critical scenario the database transaction protects against.
     *
     * Validates: Requirement 10.4
     */
    public function test_concurrent_approval_only_first_succeeds_database_remains_consistent(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        // First approval — must succeed
        $firstResponse = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $firstResponse->assertRedirect(route('owner.mosques.show', $mosque->id));

        $codeAfterFirst = $mosque->fresh()->invitation_code;
        $this->assertNotNull($codeAfterFirst);
        $this->assertSame(MosqueStatus::Active, $mosque->fresh()->status);

        // Second approval on the same mosque — must fail because status is no longer pending.
        // The FormRequest's Rule::exists(...)->where('status', 'pending') will reject it.
        $secondResponse = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // Validation failure: mosque is no longer pending → redirect back with errors
        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors(['mosque_id']);

        // Database integrity: status is still ACTIVE, invitation_code is unchanged
        $fresh = $mosque->fresh();
        $this->assertSame(MosqueStatus::Active, $fresh->status);
        $this->assertSame($codeAfterFirst, $fresh->invitation_code);

        // MosqueApproved was dispatched exactly once
        Event::assertDispatchedTimes(MosqueApproved::class, 1);
    }

    /**
     * Concurrent rejection of an already-rejected mosque is also prevented.
     * The second rejection attempt is blocked by FormRequest validation.
     *
     * Validates: Requirement 10.4
     */
    public function test_concurrent_rejection_only_first_succeeds_database_remains_consistent(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();
        $reason     = 'Dokumen pendukung tidak lengkap dan tidak memenuhi kriteria verifikasi.';

        // First rejection — must succeed
        $firstResponse = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => $reason,
            ]);

        $firstResponse->assertRedirect(route('owner.mosques.pending'));
        $this->assertSame(MosqueStatus::Rejected, $mosque->fresh()->status);

        // Second rejection on the same mosque — must fail (no longer pending)
        $secondResponse = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.pending'))
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan kedua yang juga cukup panjang untuk lulus validasi.',
            ]);

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors(['mosque_id']);

        // Database integrity: original rejection reason is preserved
        $fresh = $mosque->fresh();
        $this->assertSame(MosqueStatus::Rejected, $fresh->status);
        $this->assertSame($reason, $fresh->rejection_reason);

        // MosqueRejected was dispatched exactly once
        Event::assertDispatchedTimes(MosqueRejected::class, 1);
    }

    /**
     * Approving a mosque and then immediately attempting to reject it (race between
     * two concurrent admin sessions) is also prevented: the rejection attempt fails
     * because the mosque is no longer pending.
     *
     * Validates: Requirement 10.4
     */
    public function test_approve_then_reject_race_condition_is_prevented(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        // First: approve
        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ])
            ->assertRedirect(route('owner.mosques.show', $mosque->id));

        // Second: concurrent rejection attempt on the now-active mosque → blocked
        $rejectResponse = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan penolakan yang cukup panjang untuk validasi.',
            ]);

        $rejectResponse->assertRedirect();
        $rejectResponse->assertSessionHasErrors(['mosque_id']);

        // Status must remain ACTIVE; no rejection data should be written
        $fresh = $mosque->fresh();
        $this->assertSame(MosqueStatus::Active, $fresh->status);
        $this->assertNull($fresh->rejected_at);
        $this->assertNull($fresh->rejected_by);
    }
}
