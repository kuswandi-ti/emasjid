<?php

namespace Tests\Feature\Auth;

use App\Models\Mosque;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Tests for route protection middleware on owner and admin panels.
 *
 * Validates: Requirements 6.3, 6.4, 6.6, 7.4, 7.5, 7.6, 9.6, 9.7, 9.8, 9.9
 */
class RouteProtectionTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions required for role assignment in tests
        $this->seed(PermissionSeeder::class);
    }

    // ─── Owner panel (/owner/dashboard) ──────────────────────────────────────

    /**
     * An unauthenticated user accessing /owner/dashboard is redirected to login.
     *
     * Validates: Requirements 6.3, 9.6
     */
    public function test_unauthenticated_user_redirected_from_owner_dashboard(): void
    {
        $response = $this->get('/owner/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * A mosque-admin user (non-super-admin) is forbidden on /owner/dashboard.
     *
     * Validates: Requirements 6.4, 9.7
     */
    public function test_mosque_admin_forbidden_on_owner_dashboard(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();

        $response = $this->actingAs($mosqueAdmin)->get('/owner/dashboard');

        $response->assertForbidden();
    }

    /**
     * A super-admin user can access /owner/dashboard and receives a 200 OK.
     *
     * Validates: Requirement 6.6
     */
    public function test_super_admin_can_access_owner_dashboard(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/owner/dashboard');

        $response->assertOk();
    }

    // ─── Admin panel (/admin/dashboard) ──────────────────────────────────────

    /**
     * An unauthenticated user accessing /admin/dashboard is redirected to login.
     *
     * Validates: Requirements 7.4, 9.8
     */
    public function test_unauthenticated_user_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * A mosque-admin user with an active mosque can access /admin/dashboard.
     *
     * Validates: Requirements 7.6
     */
    public function test_mosque_admin_with_active_mosque_can_access_admin_dashboard(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();

        $response = $this->actingAs($mosqueAdmin)->get('/admin/dashboard');

        $response->assertOk();
    }

    /**
     * A mosque-admin whose mosque is suspended is redirected to the suspended page on /admin/dashboard.
     *
     * EnsureMosqueActive redirects to mosque.suspended route when mosque status is Suspended.
     *
     * Validates: Requirements 5.2, 7.5, 9.9
     */
    public function test_mosque_admin_with_suspended_mosque_is_forbidden_on_admin(): void
    {
        $mosque = Mosque::factory()->suspended()->create();

        $user = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($mosque->id);
        $user->assignRole('mosque-admin');
        $user->active_mosque_id = $mosque->id;
        $user->save();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // EnsureMosqueActive now redirects suspended mosques to mosque.suspended route
        // instead of aborting with 403, per Day 11 requirement 5.2
        $response->assertRedirect(route('mosque.suspended'));
    }

    /**
     * A super-admin with no active_mosque_id is blocked on /admin/dashboard.
     *
     * IdentifyMosque finds no mosque (active_mosque_id is null), so
     * EnsureMosqueActive receives no mosque and aborts with 404.
     *
     * Validates: Requirement 7.5
     */
    public function test_super_admin_without_mosque_context_forbidden_on_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();
        // Ensure no active mosque is set (factory default is null)
        $this->assertNull($superAdmin->active_mosque_id);

        $response = $this->actingAs($superAdmin)->get('/admin/dashboard');

        // EnsureMosqueActive aborts 404 when no mosque is resolved
        $response->assertStatus(404);
    }
}
