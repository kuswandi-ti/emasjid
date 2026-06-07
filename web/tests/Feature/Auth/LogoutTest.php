<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the logout flow.
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 9.5
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An authenticated user can POST /logout, which logs them out
     * and redirects them to the login page.
     *
     * Validates: Requirements 4.1, 4.2, 4.3, 4.4
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * After logout the session carries the success flash message
     * "Anda telah berhasil keluar."
     *
     * Validates: Requirement 4.4
     */
    public function test_logout_redirects_to_login_with_flash(): void
    {
        $user = User::factory()->create();

        // The flash is set on the redirect response itself (before following).
        $response = $this->actingAs($user)->post('/logout');

        $response->assertSessionHas('success', 'Anda telah berhasil keluar.');
        $response->assertRedirect(route('login'));
    }

    /**
     * A GET request to /logout is not allowed — the route only accepts POST.
     *
     * Validates: Requirement 4.5
     */
    public function test_get_logout_route_is_not_allowed(): void
    {
        $response = $this->get('/logout');

        $response->assertStatus(405);
    }

    /**
     * An unauthenticated user who POSTs to /logout is redirected to login
     * rather than producing an error.
     *
     * Validates: Requirement 4.5, 9.5
     */
    public function test_unauthenticated_user_cannot_post_logout(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
    }
}
