<?php

namespace Tests\Feature\Middleware;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Unit tests for EnsureOwnerAccess middleware.
 *
 * Validates: Requirements 1.2, 1.3, 1.4
 */
class EnsureOwnerAccessTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    // ─── Requirement 1.2: Unauthenticated user redirection ───────────────────

    /**
     * An unauthenticated user is redirected to the login page.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/owner/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * An unauthenticated user attempting any owner route is redirected to login.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_redirected_from_any_owner_route(): void
    {
        $this->get('/owner/mosques')->assertRedirect(route('login'));
        $this->get('/owner/mosques/pending')->assertRedirect(route('login'));
    }

    // ─── Requirement 1.3: Non-super-admin receives 403 ───────────────────────

    /**
     * A logged-in user without the super-admin role receives a 403 Forbidden.
     *
     * Validates: Requirement 1.3
     */
    public function test_non_super_admin_receives_403_forbidden(): void
    {
        $mosqueAdmin = $this->createActiveMosqueAdmin();

        $response = $this->actingAs($mosqueAdmin)->get('/owner/dashboard');

        $response->assertForbidden();
    }

    /**
     * A user with no role at all receives a 403 Forbidden.
     *
     * Validates: Requirement 1.3
     */
    public function test_user_with_no_role_receives_403_forbidden(): void
    {
        $user = $this->createUserWithNoRole();

        $response = $this->actingAs($user)->get('/owner/dashboard');

        $response->assertForbidden();
    }

    /**
     * A staff user (non-super-admin) receives 403 on any owner route.
     *
     * Validates: Requirement 1.3
     */
    public function test_staff_user_receives_403_on_owner_routes(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)->get('/owner/dashboard')->assertForbidden();
        $this->actingAs($staff)->get('/owner/mosques')->assertForbidden();
        $this->actingAs($staff)->get('/owner/mosques/pending')->assertForbidden();
    }

    // ─── Requirement 1.4: Super-admin passes through middleware ──────────────

    /**
     * A super-admin user successfully passes through the middleware (200 OK).
     *
     * Validates: Requirement 1.4
     */
    public function test_super_admin_passes_through_middleware(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/owner/dashboard');

        $response->assertOk();
    }

    /**
     * A super-admin can access all owner routes without being blocked.
     *
     * Validates: Requirement 1.4
     */
    public function test_super_admin_can_access_all_owner_routes(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin)->get('/owner/dashboard')->assertOk();
        $this->actingAs($superAdmin)->get('/owner/mosques')->assertOk();
        $this->actingAs($superAdmin)->get('/owner/mosques/pending')->assertOk();
    }
}
