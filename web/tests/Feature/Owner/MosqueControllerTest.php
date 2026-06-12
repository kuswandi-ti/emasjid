<?php

namespace Tests\Feature\Owner;

use App\Enums\MosqueStatus;
use App\Events\MosqueApproved;
use App\Events\MosqueRejected;
use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for Owner MosqueController.
 *
 * Validates: Requirements 3.1, 3.5, 3.6, 4.1, 5.1
 */
class MosqueControllerTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build an empty LengthAwarePaginator suitable for mocked service calls.
     */
    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 15);
    }

    /**
     * Build a paginator wrapping a collection of items.
     *
     * @param  array  $items
     */
    private function paginatorOf(array $items, int $perPage = 15): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), $perPage);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 3.1 – Mosque list with pagination
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Unauthenticated users are redirected to the login page when visiting the
     * mosque list.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_cannot_access_mosque_list(): void
    {
        $response = $this->get(route('owner.mosques.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Non-super-admin users receive 403 on the mosque list page.
     *
     * Validates: Requirement 1.3
     */
    public function test_non_super_admin_cannot_access_mosque_list(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();

        $response = $this->actingAs($mosqueAdmin)->get(route('owner.mosques.index'));

        $response->assertForbidden();
    }

    /**
     * The mosque list page is accessible by a super-admin.
     *
     * Validates: Requirement 3.1
     */
    public function test_super_admin_can_access_mosque_list(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->andReturn($this->emptyPaginator());
        });

        $response = $this->actingAs($superAdmin)->get(route('owner.mosques.index'));

        $response->assertOk();
        $response->assertViewIs('owner.mosques.index');
    }

    /**
     * The controller passes a paginated mosque collection to the view.
     *
     * Validates: Requirement 3.1
     */
    public function test_mosque_list_passes_mosques_and_filters_to_view(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $paginator  = $this->emptyPaginator();

        $this->mock(MosqueService::class, function ($mock) use ($paginator) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->andReturn($paginator);
        });

        $response = $this->actingAs($superAdmin)->get(route('owner.mosques.index'));

        $response->assertOk();
        $response->assertViewHas('mosques', $paginator);
        $response->assertViewHas('filters');
    }

    /**
     * The default per_page value is 15 when not explicitly provided.
     *
     * Validates: Requirement 3.1
     */
    public function test_mosque_list_uses_default_pagination_of_15(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(function (array $filters, int $perPage) {
                    return $perPage === 15;
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)->get(route('owner.mosques.index'));
    }

    /**
     * A custom per_page value is forwarded to the service.
     *
     * Validates: Requirement 3.1
     */
    public function test_mosque_list_accepts_custom_per_page(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(function (array $filters, int $perPage) {
                    return $perPage === 25;
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)->get(route('owner.mosques.index', ['per_page' => 25]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 3.5 – Filter by status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A status filter is forwarded to the service as part of the filters array.
     *
     * Validates: Requirement 3.5
     */
    public function test_status_filter_is_passed_to_service(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(function (array $filters, int $perPage) {
                    return isset($filters['status']) && $filters['status'] === 'active';
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'active']));
    }

    /**
     * The current filter values are passed back to the view so the form can
     * pre-populate its inputs.
     *
     * Validates: Requirement 3.5
     */
    public function test_filters_are_returned_to_view(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->andReturn($this->emptyPaginator());
        });

        $response = $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'pending']));

        $filters = $response->viewData('filters');

        $this->assertIsArray($filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertSame('pending', $filters['status']);
    }

    /**
     * "active" status filter is forwarded correctly to the service.
     *
     * Validates: Requirement 3.5
     */
    public function test_active_status_value_is_forwarded(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(fn (array $f) => ($f['status'] ?? null) === 'active')
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'active']))
            ->assertOk();
    }

    /**
     * "suspended" status filter is forwarded correctly to the service.
     *
     * Validates: Requirement 3.5
     */
    public function test_suspended_status_value_is_forwarded(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(fn (array $f) => ($f['status'] ?? null) === 'suspended')
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'suspended']))
            ->assertOk();
    }

    /**
     * "rejected" status filter is forwarded correctly to the service.
     *
     * Validates: Requirement 3.5
     */
    public function test_rejected_status_value_is_forwarded(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(fn (array $f) => ($f['status'] ?? null) === 'rejected')
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'rejected']))
            ->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 3.6 – Search functionality
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A search term is forwarded to the service inside the filters array.
     *
     * Validates: Requirement 3.6
     */
    public function test_search_filter_is_passed_to_service(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(function (array $filters) {
                    return isset($filters['search']) && $filters['search'] === 'Jakarta';
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['search' => 'Jakarta']));
    }

    /**
     * Combined status and search filters are both forwarded to the service.
     *
     * Validates: Requirements 3.5, 3.6
     */
    public function test_combined_status_and_search_filters_are_passed(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueList')
                ->once()
                ->withArgs(function (array $filters) {
                    return ($filters['status'] ?? null) === 'active'
                        && ($filters['search'] ?? null) === 'Bandung';
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.index', ['status' => 'active', 'search' => 'Bandung']));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 4.1 – Pending mosque page
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The pending mosque page is accessible by a super-admin.
     *
     * Validates: Requirement 4.1
     */
    public function test_super_admin_can_access_pending_mosque_page(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getPendingMosques')
                ->once()
                ->andReturn($this->emptyPaginator());
        });

        $response = $this->actingAs($superAdmin)->get(route('owner.mosques.pending'));

        $response->assertOk();
        $response->assertViewIs('owner.mosques.pending');
    }

    /**
     * Unauthenticated users are redirected to login when accessing pending page.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_cannot_access_pending_page(): void
    {
        $response = $this->get(route('owner.mosques.pending'));

        $response->assertRedirect(route('login'));
    }

    /**
     * The pending page service call includes only pending mosques (no status
     * filter is accepted – the controller always calls getPendingMosques).
     *
     * Validates: Requirement 4.1
     */
    public function test_pending_page_calls_get_pending_mosques_on_service(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $paginator  = $this->emptyPaginator();

        $this->mock(MosqueService::class, function ($mock) use ($paginator) {
            $mock->shouldReceive('getPendingMosques')
                ->once()
                ->andReturn($paginator);
            // getMosqueList must NOT be called on the pending route
            $mock->shouldNotReceive('getMosqueList');
        });

        $response = $this->actingAs($superAdmin)->get(route('owner.mosques.pending'));

        $response->assertOk();
        $response->assertViewHas('mosques', $paginator);
    }

    /**
     * A search term is forwarded to the pending mosques service call.
     *
     * Validates: Requirement 4.1
     */
    public function test_pending_page_forwards_search_to_service(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getPendingMosques')
                ->once()
                ->withArgs(function (?string $search, int $perPage) {
                    return $search === 'Al Ikhlas' && $perPage === 15;
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)
            ->get(route('owner.mosques.pending', ['search' => 'Al Ikhlas']));
    }

    /**
     * No search term means null is forwarded to the service.
     *
     * Validates: Requirement 4.1
     */
    public function test_pending_page_passes_null_search_when_omitted(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getPendingMosques')
                ->once()
                ->withArgs(function (?string $search) {
                    return $search === null;
                })
                ->andReturn($this->emptyPaginator());
        });

        $this->actingAs($superAdmin)->get(route('owner.mosques.pending'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 5.1 – Mosque detail page
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * The mosque detail page is accessible by a super-admin and returns the
     * correct view with mosque data.
     *
     * Validates: Requirement 5.1
     */
    public function test_super_admin_can_access_mosque_detail(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create();

        $this->mock(MosqueService::class, function ($mock) use ($mosque) {
            $mock->shouldReceive('getMosqueDetail')
                ->once()
                ->with($mosque->id)
                ->andReturn($mosque);
        });

        $response = $this->actingAs($superAdmin)
            ->get(route('owner.mosques.show', $mosque->id));

        $response->assertOk();
        $response->assertViewIs('owner.mosques.show');
        $response->assertViewHas('mosque', $mosque);
    }

    /**
     * Accessing a non-existent mosque returns HTTP 404.
     *
     * Validates: Requirement 5.1 (AC 5.2)
     */
    public function test_non_existent_mosque_returns_404(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->mock(MosqueService::class, function ($mock) {
            $mock->shouldReceive('getMosqueDetail')
                ->once()
                ->andThrow(new ModelNotFoundException('Mosque not found'));
        });

        $response = $this->actingAs($superAdmin)
            ->get(route('owner.mosques.show', 99999));

        $response->assertNotFound();
    }

    /**
     * Unauthenticated users are redirected to login for the detail page.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_cannot_access_mosque_detail(): void
    {
        $response = $this->get(route('owner.mosques.show', 1));

        $response->assertRedirect(route('login'));
    }

    /**
     * A non-super-admin receives 403 on the mosque detail page.
     *
     * Validates: Requirement 5.1 (AC 5.9)
     */
    public function test_non_super_admin_cannot_access_mosque_detail(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->create();

        $response = $this->actingAs($mosqueAdmin)
            ->get(route('owner.mosques.show', $mosque->id));

        $response->assertForbidden();
    }

    /**
     * The detail page passes a complete mosque model (with relationships) to
     * the view.
     *
     * Validates: Requirement 5.1
     */
    public function test_mosque_detail_view_receives_complete_mosque_data(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->create();

        // Attach members_count and total_donations as the repository would
        $mosque->setAttribute('members_count', 42);
        $mosque->setAttribute('total_donations', 500000);

        $this->mock(MosqueService::class, function ($mock) use ($mosque) {
            $mock->shouldReceive('getMosqueDetail')
                ->once()
                ->andReturn($mosque);
        });

        $response = $this->actingAs($superAdmin)
            ->get(route('owner.mosques.show', $mosque->id));

        $response->assertOk();

        /** @var Mosque $viewMosque */
        $viewMosque = $response->viewData('mosque');

        $this->assertSame($mosque->id, $viewMosque->id);
        $this->assertSame(42, $viewMosque->members_count);
        $this->assertSame(500000, $viewMosque->total_donations);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirements 1.10, 1.11, 7.4, 7.5 – Approve mosque
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Authenticated super-admin can approve a pending mosque.
     * On success the service is called and the response redirects to detail page.
     *
     * Validates: Requirements 1.10, 1.11, 7.4, 7.5
     */
    public function test_super_admin_can_approve_pending_mosque(): void
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

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    /**
     * Approval redirects to the mosque detail page with a success flash message.
     *
     * Validates: Requirements 1.11, 7.5
     */
    public function test_approval_redirects_to_mosque_detail_with_success_message(): void
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
     * Approving a mosque dispatches the MosqueApproved event.
     *
     * Validates: Requirement 1.10
     */
    public function test_approve_dispatches_mosque_approved_event(): void
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
     * Approving a mosque generates and persists an invitation code.
     *
     * Validates: Requirements 1.5, 1.6
     */
    public function test_approve_generates_invitation_code(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        // Pending factory sets status=pending but leaves invitation_code set (NOT NULL constraint).
        // After approval, invitation code should be updated to a new 6-char code.
        $mosque = Mosque::factory()->pending()->create();

        $oldCode = $mosque->invitation_code;

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $fresh = $mosque->fresh();
        $this->assertNotNull($fresh->invitation_code);
        // Approved code is 6 chars uppercase alphanumeric
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $fresh->invitation_code);
    }

    /**
     * Approving a non-pending mosque causes a redirect back with validation errors in session.
     * The FormRequest validates that mosque_id must reference a pending mosque.
     *
     * Validates: Requirements 1.12, 7.4
     */
    public function test_approving_non_pending_mosque_returns_error(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        // Active mosque – status is not pending
        $mosque = Mosque::factory()->create(); // default is Active

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        // The form request validates that mosque_id references a pending mosque,
        // so a non-pending mosque causes validation failure → redirect back with errors.
        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        // Status should remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Active->value,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirements 2.10, 2.11, 7.9, 7.10 – Reject mosque
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Authenticated super-admin can reject a pending mosque with a valid reason.
     *
     * Validates: Requirements 2.10, 2.11, 7.9, 7.10
     */
    public function test_super_admin_can_reject_pending_mosque(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Dokumen tidak lengkap dan informasi tidak valid.',
            ]);

        $response->assertRedirect(route('owner.mosques.pending'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Rejected->value,
        ]);
    }

    /**
     * Rejection redirects to the pending mosque list with a success flash message.
     *
     * Validates: Requirements 2.11, 7.10
     */
    public function test_rejection_redirects_to_pending_list_with_success_message(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Informasi tidak mencukupi untuk proses verifikasi.',
            ]);

        $response->assertRedirect(route('owner.mosques.pending'));
        $response->assertSessionHas('success');
    }

    /**
     * Rejection dispatches the MosqueRejected event.
     *
     * Validates: Requirement 2.10
     */
    public function test_reject_dispatches_mosque_rejected_event(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $this->actingAs($superAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Dokumen tidak lengkap untuk proses review.',
            ]);

        Event::assertDispatched(MosqueRejected::class, function (MosqueRejected $event) use ($mosque) {
            return $event->mosque->id === $mosque->id;
        });
    }

    /**
     * Rejecting a non-pending mosque causes a redirect back with validation errors.
     * The FormRequest enforces that mosque_id must reference a pending mosque.
     *
     * Validates: Requirements 2.12, 7.9
     */
    public function test_rejecting_non_pending_mosque_returns_error(): void
    {
        Event::fake();

        $superAdmin = $this->createSuperAdmin();
        // Rejected mosque – no longer pending
        $mosque = Mosque::factory()->rejected()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.pending'))
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan yang cukup panjang untuk validasi.',
            ]);

        // The form request will fail because the mosque is not pending
        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);

        // Status should remain unchanged
        $this->assertDatabaseHas('mosques', [
            'id'     => $mosque->id,
            'status' => MosqueStatus::Rejected->value,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirements 10.1, 10.2 – Authorization enforcement
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * A non-super-admin (mosque-admin role) receives 403 when trying to approve.
     *
     * Validates: Requirement 10.1
     */
    public function test_non_super_admin_cannot_approve_mosque(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->pending()->create();

        $response = $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();
    }

    /**
     * A non-super-admin (mosque-admin role) receives 403 when trying to reject.
     *
     * Validates: Requirement 10.1
     */
    public function test_non_super_admin_cannot_reject_mosque(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();
        $mosque      = Mosque::factory()->pending()->create();

        $response = $this->actingAs($mosqueAdmin)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan yang cukup panjang untuk validasi.',
            ]);

        $response->assertForbidden();
    }

    /**
     * A user with no role receives 403 when trying to approve.
     *
     * Validates: Requirement 10.2
     */
    public function test_user_with_no_role_cannot_approve_mosque(): void
    {
        $user   = $this->createUserWithNoRole();
        $mosque = Mosque::factory()->pending()->create();

        $response = $this->actingAs($user)
            ->post(route('owner.mosques.approve', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertForbidden();
    }

    /**
     * A user with no role receives 403 when trying to reject.
     *
     * Validates: Requirement 10.2
     */
    public function test_user_with_no_role_cannot_reject_mosque(): void
    {
        $user   = $this->createUserWithNoRole();
        $mosque = Mosque::factory()->pending()->create();

        $response = $this->actingAs($user)
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Alasan yang cukup panjang untuk validasi.',
            ]);

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Requirement 10.3 – Unauthenticated user redirects to login
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Unauthenticated user is redirected to login when trying to approve.
     *
     * Validates: Requirement 10.3
     */
    public function test_unauthenticated_user_redirects_to_login_on_approve(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $response = $this->post(route('owner.mosques.approve', $mosque->id), [
            'mosque_id' => $mosque->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Unauthenticated user is redirected to login when trying to reject.
     *
     * Validates: Requirement 10.3
     */
    public function test_unauthenticated_user_redirects_to_login_on_reject(): void
    {
        $mosque = Mosque::factory()->pending()->create();

        $response = $this->post(route('owner.mosques.reject', $mosque->id), [
            'mosque_id'        => $mosque->id,
            'rejection_reason' => 'Alasan penolakan yang cukup panjang.',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validation errors – HTTP 422 / redirect with errors
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Missing mosque_id on approve request causes a redirect back with validation errors.
     * On web routes, FormRequest validation failures redirect rather than returning JSON 422.
     *
     * Validates: Requirement 10.3 (Form Request validation)
     */
    public function test_approve_without_mosque_id_returns_422(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.show', $mosque->id))
            ->post(route('owner.mosques.approve', $mosque->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);
    }

    /**
     * Missing rejection_reason on reject request causes a redirect back with validation errors.
     *
     * Validates: Requirement 10.3 (Form Request validation)
     */
    public function test_reject_without_rejection_reason_returns_422(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.pending'))
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id' => $mosque->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['rejection_reason']);
    }

    /**
     * A rejection_reason that is too short (< 10 characters) causes redirect with errors.
     *
     * Validates: Requirement 10.3 (Form Request validation)
     */
    public function test_reject_with_short_rejection_reason_returns_422(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $mosque     = Mosque::factory()->pending()->create();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.pending'))
            ->post(route('owner.mosques.reject', $mosque->id), [
                'mosque_id'        => $mosque->id,
                'rejection_reason' => 'Short',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['rejection_reason']);
    }

    /**
     * Submitting a non-existent mosque_id causes redirect with validation errors.
     *
     * Validates: Requirement 10.3 (Form Request validation)
     */
    public function test_approve_with_nonexistent_mosque_id_returns_422(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)
            ->from(route('owner.mosques.pending'))
            ->post(route('owner.mosques.approve', 99999), [
                'mosque_id' => 99999,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['mosque_id']);
    }
}
