<?php

namespace Tests\Feature\Owner;

use App\Services\DashboardService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for Owner Dashboard access.
 *
 * Validates: Requirements 1.2, 1.3, 2.7
 */
class DashboardTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    // ─── Requirement 1.2: Unauthenticated user redirected to login ───────────

    /**
     * Unauthenticated access to the dashboard redirects to the login page.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('owner.dashboard'));

        $response->assertRedirect(route('login'));
    }

    // ─── Requirement 1.3: Non-super-admin receives 403 ───────────────────────

    /**
     * A logged-in mosque admin (non-super-admin) receives 403 Forbidden.
     *
     * Validates: Requirement 1.3
     */
    public function test_non_super_admin_receives_403(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();

        $response = $this->actingAs($mosqueAdmin)->get(route('owner.dashboard'));

        $response->assertForbidden();
    }

    /**
     * A user with no role receives 403 Forbidden on the dashboard.
     *
     * Validates: Requirement 1.3
     */
    public function test_user_with_no_role_receives_403(): void
    {
        $user = $this->createUserWithNoRole();

        $response = $this->actingAs($user)->get(route('owner.dashboard'));

        $response->assertForbidden();
    }

    // ─── Requirement 2.7: Super-admin can access dashboard with statistics ───

    /**
     * A super-admin can access the dashboard and receives a 200 OK response.
     *
     * Validates: Requirements 1.4, 2.7
     */
    public function test_super_admin_can_access_dashboard(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('owner.dashboard'));

        $response->assertOk();
    }

    /**
     * The dashboard view receives the statistics data from DashboardService.
     *
     * Validates: Requirement 2.7
     */
    public function test_super_admin_dashboard_receives_statistics(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $fakeStatistics = [
            'total_mosques_active'           => 5,
            'total_mosques_pending'          => 2,
            'total_mosques_suspended'        => 1,
            'total_congregation'             => 100,
            'total_platform_fees'            => 1250000,
            'total_platform_fees_formatted'  => 'Rp 1.250.000',
        ];

        $this->mock(DashboardService::class, function ($mock) use ($fakeStatistics) {
            $mock->shouldReceive('getStatistics')
                ->once()
                ->andReturn($fakeStatistics);
        });

        $response = $this->actingAs($superAdmin)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertViewHas('statistics', $fakeStatistics);
    }

    /**
     * The dashboard view has access to each required statistics key.
     *
     * Validates: Requirement 2.7
     */
    public function test_dashboard_view_contains_all_statistics_keys(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('owner.dashboard'));

        $response->assertOk();

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('total_mosques_active', $statistics);
        $this->assertArrayHasKey('total_mosques_pending', $statistics);
        $this->assertArrayHasKey('total_mosques_suspended', $statistics);
        $this->assertArrayHasKey('total_congregation', $statistics);
        $this->assertArrayHasKey('total_platform_fees', $statistics);
        $this->assertArrayHasKey('total_platform_fees_formatted', $statistics);
    }
}
