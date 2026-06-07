<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Validates: Requirements 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 3.1, 3.2, 3.5, 9.1, 9.2, 9.3, 9.4, 9.10
 */
class LoginTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions so Spatie permissions are available
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    /**
     * Validates: Requirements 2.1, 9.1
     */
    public function test_super_admin_is_redirected_to_owner_dashboard(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('owner.dashboard'));
    }

    /**
     * Validates: Requirements 2.2, 9.2
     */
    public function test_mosque_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = $this->createActiveMosqueAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Validates: Requirements 2.3
     */
    public function test_staff_is_redirected_to_admin_dashboard(): void
    {
        $user = $this->createStaffUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Validates: Requirements 3.1, 3.2, 9.3
     */
    public function test_wrong_password_returns_email_error(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('Email atau password salah.', $errors->first('email'));
    }

    /**
     * Validates: Requirements 9.4
     */
    public function test_empty_form_returns_validation_errors(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Validates: Requirements 1.5
     */
    public function test_invalid_email_format_returns_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'not-an-email',
            'password' => 'somepassword',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Validates: Requirements 2.5
     */
    public function test_user_with_no_role_is_logged_out_with_error(): void
    {
        $user = $this->createUserWithNoRole();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Validates: Requirements 3.5, 9.10
     */
    public function test_rate_limit_locks_after_five_attempts(): void
    {
        // Use a unique email per test run to avoid cross-test pollution
        $email = 'ratelimit_'.Str::random(8).'@example.com';

        // Clear any existing rate limit for this key
        $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');
        RateLimiter::clear($throttleKey);

        // Attempt login 6 times with wrong credentials
        $response = null;
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        // The 6th attempt should trigger the rate limit lockout
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            $errors->first('email')
        );
    }

    /**
     * Validates: Requirements 2.6
     */
    public function test_session_is_regenerated_on_successful_login(): void
    {
        $user = $this->createSuperAdmin();

        // Capture the session ID before login
        $this->get('/login'); // initialise session
        $sessionIdBefore = session()->getId();

        // Perform login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $sessionIdAfter = session()->getId();

        $this->assertNotEquals($sessionIdBefore, $sessionIdAfter);
    }

    /**
     * Validates: Requirements 2.7
     */
    public function test_success_flash_is_set_on_successful_login(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Follow the redirect and check the session has the 'success' key
        $response->assertSessionHas('success');
    }

    /**
     * Validates: Guest middleware behaviour
     */
    public function test_authenticated_user_on_login_page_is_redirected(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect();
    }
}
