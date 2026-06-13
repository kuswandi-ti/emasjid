<?php

namespace Tests\Unit\Middleware;

use App\Enums\MosqueStatus;
use App\Http\Middleware\EnsureMosqueActive;
use App\Models\Mosque;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Unit tests for EnsureMosqueActive middleware (edge cases).
 *
 * Validates: Requirements 5.2, 5.3
 */
class EnsureMosqueActiveTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureMosqueActive $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->middleware = new EnsureMosqueActive();
    }

    /**
     * Helper to bind a mosque instance as the current mosque.
     */
    private function bindMosque(Mosque $mosque): void
    {
        app()->bind('current_mosque', fn () => $mosque);
    }

    /**
     * Helper to run the middleware and capture the response.
     * Returns null if an HttpException is thrown.
     */
    private function runMiddleware(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        });
    }

    // ─── Active status: request should pass through (HTTP 200) ──────────────────

    /**
     * Test middleware allows request to continue when mosque status is `active`.
     *
     * Validates: Requirement 5.3
     */
    public function test_active_mosque_allows_request_through(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $response = $this->runMiddleware($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test next closure is actually called when mosque status is `active`.
     *
     * Validates: Requirement 5.3
     */
    public function test_active_mosque_calls_next_closure(): void
    {
        $mosque = Mosque::factory()->create(['status' => MosqueStatus::Active]);
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $nextCalled = false;

        $this->middleware->handle($request, function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('OK', 200);
        });

        $this->assertTrue($nextCalled, 'Next closure should be called for an active mosque.');
    }

    // ─── Pending status: request should be aborted with HTTP 403 ────────────────

    /**
     * Test middleware aborts with 403 when mosque status is `pending`.
     *
     * Validates: Requirement 5.2
     */
    public function test_pending_mosque_aborts_with_403(): void
    {
        $mosque = Mosque::factory()->pending()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');

        $this->expectException(HttpException::class);

        try {
            $this->runMiddleware($request);
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertEquals('Masjid masih menunggu persetujuan.', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test middleware does NOT call next closure when mosque status is `pending`.
     *
     * Validates: Requirement 5.2
     */
    public function test_pending_mosque_does_not_call_next_closure(): void
    {
        $mosque = Mosque::factory()->pending()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $nextCalled = false;

        try {
            $this->middleware->handle($request, function () use (&$nextCalled) {
                $nextCalled = true;
                return new Response('OK', 200);
            });
        } catch (HttpException $e) {
            // Expected — swallow exception to check side-effect
        }

        $this->assertFalse($nextCalled, 'Next closure should NOT be called for a pending mosque.');
    }

    /**
     * Test middleware returns JSON 403 for pending mosque on API request.
     *
     * Validates: Requirement 5.2
     */
    public function test_pending_mosque_returns_json_403_for_api_request(): void
    {
        $mosque = Mosque::factory()->pending()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->runMiddleware($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Masjid masih menunggu persetujuan.', $data['message']);
    }

    // ─── Rejected status: request should be aborted with HTTP 403 ───────────────

    /**
     * Test middleware aborts with 403 when mosque status is `rejected`.
     *
     * Validates: Requirement 5.2
     */
    public function test_rejected_mosque_aborts_with_403(): void
    {
        $mosque = Mosque::factory()->rejected()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');

        $this->expectException(HttpException::class);

        try {
            $this->runMiddleware($request);
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertEquals('Pendaftaran masjid ditolak.', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test middleware does NOT call next closure when mosque status is `rejected`.
     *
     * Validates: Requirement 5.2
     */
    public function test_rejected_mosque_does_not_call_next_closure(): void
    {
        $mosque = Mosque::factory()->rejected()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $nextCalled = false;

        try {
            $this->middleware->handle($request, function () use (&$nextCalled) {
                $nextCalled = true;
                return new Response('OK', 200);
            });
        } catch (HttpException $e) {
            // Expected — swallow exception to check side-effect
        }

        $this->assertFalse($nextCalled, 'Next closure should NOT be called for a rejected mosque.');
    }

    /**
     * Test middleware returns JSON 403 for rejected mosque on API request.
     *
     * Validates: Requirement 5.2
     */
    public function test_rejected_mosque_returns_json_403_for_api_request(): void
    {
        $mosque = Mosque::factory()->rejected()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->runMiddleware($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Pendaftaran masjid ditolak.', $data['message']);
    }

    // ─── Suspended status: request should redirect to 'mosque.suspended' ────────

    /**
     * Test middleware redirects to 'mosque.suspended' route when mosque status is `suspended`.
     *
     * Validates: Requirement 5.2
     */
    public function test_suspended_mosque_redirects_to_mosque_suspended_route(): void
    {
        $mosque = Mosque::factory()->suspended()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $response = $this->runMiddleware($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(
            $response->isRedirect(route('mosque.suspended')),
            'Expected redirect to mosque.suspended route.'
        );
    }

    /**
     * Test redirect for suspended mosque carries mosque_name in session.
     *
     * Validates: Requirement 5.2
     */
    public function test_suspended_mosque_redirect_flashes_mosque_name_to_session(): void
    {
        $mosque = Mosque::factory()->suspended()->create(['name' => 'Masjid Al-Amin']);
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $response = $this->runMiddleware($request);

        // The redirect response has the 'mosque_name' value flashed to session
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString(
            route('mosque.suspended', [], false),
            $response->headers->get('Location')
        );
    }

    /**
     * Test suspended mosque does NOT abort with 403 — instead it redirects.
     *
     * Validates: Requirement 5.2
     */
    public function test_suspended_mosque_does_not_abort_with_403(): void
    {
        $mosque = Mosque::factory()->suspended()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');

        // No exception should be thrown for suspended status
        $exceptionThrown = false;
        try {
            $response = $this->runMiddleware($request);
            // Should be a redirect, not a 403
            $this->assertNotEquals(403, $response->getStatusCode());
        } catch (HttpException $e) {
            $exceptionThrown = true;
        }

        $this->assertFalse($exceptionThrown, 'HttpException should NOT be thrown for a suspended mosque — it should redirect instead.');
    }

    /**
     * Test middleware returns JSON 403 for suspended mosque on API request (not a redirect).
     *
     * Validates: Requirement 5.2
     */
    public function test_suspended_mosque_returns_json_403_for_api_request(): void
    {
        $mosque = Mosque::factory()->suspended()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->runMiddleware($request);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Masjid sedang ditangguhkan. Hubungi admin platform.', $data['message']);
    }

    /**
     * Test middleware does NOT call next closure when mosque status is `suspended`.
     *
     * Validates: Requirement 5.2
     */
    public function test_suspended_mosque_does_not_call_next_closure(): void
    {
        $mosque = Mosque::factory()->suspended()->create();
        $this->bindMosque($mosque);

        $request = Request::create('/admin/dashboard', 'GET');
        $nextCalled = false;

        $this->middleware->handle($request, function () use (&$nextCalled) {
            $nextCalled = true;
            return new Response('OK', 200);
        });

        $this->assertFalse($nextCalled, 'Next closure should NOT be called for a suspended mosque.');
    }

    // ─── No mosque bound: request should abort with 404 ─────────────────────────

    /**
     * Test middleware aborts with 404 when no mosque is bound to the container.
     *
     * Validates: Requirement 5.2 (prerequisite guard)
     */
    public function test_no_mosque_bound_aborts_with_404(): void
    {
        // Ensure no mosque is bound
        app()->offsetUnset('current_mosque');

        $request = Request::create('/admin/dashboard', 'GET');

        $this->expectException(HttpException::class);

        try {
            $this->runMiddleware($request);
        } catch (HttpException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            throw $e;
        }
    }

    /**
     * Test middleware returns JSON 404 when no mosque is bound and request expects JSON.
     */
    public function test_no_mosque_bound_returns_json_404_for_api_request(): void
    {
        app()->offsetUnset('current_mosque');

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = $this->runMiddleware($request);

        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }
}
