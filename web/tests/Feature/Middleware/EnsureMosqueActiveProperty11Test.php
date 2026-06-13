<?php

// Feature: day11-mosque-suspend-reactivate, Property 11

namespace Tests\Feature\Middleware;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Property 11: EnsureMosqueActive memblokir akses masjid suspended
 *
 * Validates: Requirements 5.2, 5.4
 *
 * Jalankan 100 iterasi: buat masjid `suspended` acak, kirim request ke route
 * yang dilindungi, assert redirect (bukan 200 bukan 403) ke `mosque.suspended`.
 * Assert tidak ada redirect loop (response bukan redirect ke dirinya sendiri).
 */
class EnsureMosqueActiveProperty11Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    /**
     * Property 11: For any suspended mosque, a GET request to any admin route
     * protected by `mosque.active` middleware MUST result in a 3xx redirect to
     * `route('mosque.suspended')`, never a 200 OK or 403 Forbidden.
     * Additionally, the redirect target MUST NOT be the current request URL
     * (no redirect loop).
     *
     * Validates: Requirements 5.2, 5.4
     */
    public function test_ensure_mosque_active_blocks_suspended_mosque_and_redirects(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 11

        $protectedRoute = '/admin/dashboard';
        $suspendedPageUrl = route('mosque.suspended');

        for ($i = 0; $i < 100; $i++) {
            // Create a random suspended mosque (with an associated admin user)
            $mosque = Mosque::factory()->suspended()->create();

            // Create a mosque-admin user scoped to this mosque
            $user = User::factory()->create([
                'active_mosque_id' => $mosque->id,
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($mosque->id);
            $user->assignRole('mosque-admin');

            // Bind the suspended mosque directly into the IoC container so
            // IdentifyMosque is effectively bypassed and EnsureMosqueActive
            // sees this specific suspended mosque.
            app()->instance('current_mosque', $mosque);

            $response = $this->actingAs($user)->get($protectedRoute);

            // Assert: must be a redirect (3xx), not 200 and not 403
            $this->assertNotEquals(
                200,
                $response->getStatusCode(),
                "Iteration {$i}: response should NOT be 200 for suspended mosque id={$mosque->id}"
            );

            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Iteration {$i}: response should NOT be 403 for suspended mosque (expected redirect, not abort)"
            );

            $response->assertRedirect();

            // Assert: redirect target is route('mosque.suspended')
            $response->assertRedirect($suspendedPageUrl);

            // Assert: no redirect loop — the redirect target must not be the
            // same URL as the current request URL.
            $this->assertNotEquals(
                url($protectedRoute),
                $response->headers->get('Location'),
                "Iteration {$i}: redirect loop detected — target URL is the same as the current URL"
            );
        }
    }
}
