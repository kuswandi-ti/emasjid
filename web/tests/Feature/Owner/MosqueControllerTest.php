<?php

namespace Tests\Feature\Owner;

use App\Models\Mosque;
use App\Models\User;
use App\Services\MosqueService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
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
}
