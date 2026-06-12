<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureOwnerAccess;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Unit tests for EnsureOwnerAccess middleware.
 *
 * Validates: Requirements 1.2, 1.3, 1.4
 */
class EnsureOwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureOwnerAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions required for role assignment
        $this->seed(PermissionSeeder::class);

        $this->middleware = new EnsureOwnerAccess();
    }

    /**
     * Test unauthenticated user is redirected to login page.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $request = Request::create('/owner/dashboard', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect(route('login')));
    }

    /**
     * Test unauthenticated user receives JSON response for API requests.
     *
     * Validates: Requirement 1.2
     */
    public function test_unauthenticated_user_receives_json_for_api_request(): void
    {
        $request = Request::create('/owner/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Unauthenticated.', $data['message']);
    }

    /**
     * Test non-super-admin user receives 403 Forbidden response.
     *
     * Validates: Requirement 1.3
     */
    public function test_non_super_admin_user_receives_403_forbidden(): void
    {
        // Create a regular user without super-admin role
        $user = User::factory()->create();

        $request = Request::create('/owner/dashboard', 'GET');
        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Akses ditolak. Hanya super admin yang dapat mengakses halaman ini.');

        try {
            $this->middleware->handle($request, function () {
                return new Response('Success');
            });
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    /**
     * Test mosque-admin user (non-super-admin) receives 403 Forbidden response.
     *
     * Validates: Requirement 1.3
     */
    public function test_mosque_admin_user_receives_403_forbidden(): void
    {
        // Create a mosque-admin user (not super-admin)
        $user = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->assignRole('mosque-admin');

        $request = Request::create('/owner/dashboard', 'GET');
        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Akses ditolak. Hanya super admin yang dapat mengakses halaman ini.');

        try {
            $this->middleware->handle($request, function () {
                return new Response('Success');
            });
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    /**
     * Test non-super-admin user receives JSON 403 for API requests.
     *
     * Validates: Requirement 1.3
     */
    public function test_non_super_admin_user_receives_json_403_for_api_request(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/owner/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');
        $this->actingAs($user);

        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Akses ditolak. Hanya super admin yang dapat mengakses halaman ini.', $data['message']);
    }

    /**
     * Test super-admin user successfully passes through middleware.
     *
     * Validates: Requirement 1.4
     */
    public function test_super_admin_user_passes_through_middleware(): void
    {
        // Create super-admin user with platform-level role (team_id = 0)
        $user = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $user->assignRole('super-admin');

        $request = Request::create('/owner/dashboard', 'GET');
        $this->actingAs($user);

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('Success', 200);
        });

        $this->assertTrue($called, 'Next middleware closure should be called');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /**
     * Test super-admin user can pass through middleware multiple times.
     *
     * Validates: Requirement 1.4
     */
    public function test_super_admin_user_can_access_multiple_times(): void
    {
        $user = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $user->assignRole('super-admin');

        $this->actingAs($user);

        // First request
        $request1 = Request::create('/owner/dashboard', 'GET');
        $response1 = $this->middleware->handle($request1, function () {
            return new Response('Success', 200);
        });
        $this->assertEquals(200, $response1->getStatusCode());

        // Second request
        $request2 = Request::create('/owner/mosques', 'GET');
        $response2 = $this->middleware->handle($request2, function () {
            return new Response('Success', 200);
        });
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * Test middleware properly restores team context after checking role.
     *
     * This ensures the middleware doesn't affect other role checks in the same request.
     *
     * Validates: Requirement 1.4
     */
    public function test_middleware_restores_team_context_after_check(): void
    {
        $user = User::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId(0);
        $user->assignRole('super-admin');

        $registrar = app(PermissionRegistrar::class);

        // Set a different team context before middleware
        $registrar->setPermissionsTeamId(5);
        $this->assertEquals(5, $registrar->getPermissionsTeamId());

        $request = Request::create('/owner/dashboard', 'GET');
        $this->actingAs($user);

        $this->middleware->handle($request, function () {
            return new Response('Success', 200);
        });

        // Team context should be restored to original value
        $this->assertEquals(5, $registrar->getPermissionsTeamId());
    }
}
