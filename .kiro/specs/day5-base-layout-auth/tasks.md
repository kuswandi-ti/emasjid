# Implementation Plan: Day 5 — Login & Auth Wiring (Web)

## Overview

This plan implements the authentication wiring for the EMasjid web panels. The existing building blocks (login view, layouts, middleware classes, placeholder routes) are already in place. The work is: create `LoginRequest`, create `LoginController`, update `routes/web.php` with protected route groups, create the `flash-messages` Blade component, and write feature tests. No database changes are needed.

All paths are relative to `web/` within the monorepo.

## Tasks

- [x] 1. Create `LoginRequest` form request
  - Create `app/Http/Requests/Auth/LoginRequest.php`
  - Declare rules: `email` (required, string, email), `password` (required, string, min:8), `remember` (boolean)
  - Implement `throttleKey()` method: `Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip())`
  - Implement `ensureIsNotRateLimited()`: uses `RateLimiter::tooManyAttempts()`, fires `Lockout` event, throws `ValidationException` with message "Terlalu banyak percobaan login. Coba lagi dalam 1 menit." if locked
  - Implement `authenticate()`: calls `Auth::attempt()`, increments `RateLimiter` on failure, throws `ValidationException` with `withErrors(['email' => 'Email atau password salah.'])` on failure, calls `RateLimiter::clear()` on success
  - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.4, 3.5_

- [x] 2. Create `LoginController`
  - Create `app/Http/Controllers/Auth/LoginController.php`
  - Implement `create()`: returns `view('auth.login')`, redirects authenticated users to their dashboard
  - Implement `store(LoginRequest $request)`:
    - Calls `$request->authenticate()` (validation + throttle + Auth::attempt)
    - Calls `$request->session()->regenerate()`
    - Calls private `resolveRedirect()` to get destination URL
    - If null redirect: calls `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, redirects to login with `withErrors(['email' => 'Akun Anda tidak memiliki akses ke panel ini.'])`
    - If valid redirect: returns `redirect()->intended($redirect)->with('success', 'Selamat datang, ' . Auth::user()->name . '!')`
  - Implement `destroy(Request $request)`: calls `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, redirects to `login` with flash `success` "Anda telah berhasil keluar."
  - Implement private `resolveRedirect()`: sets `PermissionsTeamId(0)` then checks `hasRole('super-admin')`; resets team ID then checks `hasRole('mosque-admin')` or `hasRole('staff')`; returns null if no match
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.3, 4.1, 4.2, 4.3, 4.4_

- [x] 3. Create `flash-messages` Blade component
  - Create `resources/views/components/flash-messages.blade.php`
  - Check `session('success')` and `session('error')` and emit SweetAlert2 toast calls
  - Use `icon: 'success'` / `icon: 'error'`, `toast: true`, `position: 'top-end'`, `showConfirmButton: false`, `timer: 3000`, `timerProgressBar: true`
  - Wrap in `DOMContentLoaded` listener
  - Include the component in the base layout (`layouts/app.blade.php`) after `@stack('scripts')` so it applies to all views
  - _Requirements: 8.1, 8.2, 8.3, 8.5_

- [x] 4. Update `routes/web.php` with protected route groups
  - Replace all closure-based placeholder routes with controller routes and middleware groups
  - Import `App\Http\Controllers\Auth\LoginController`
  - Add `Route::middleware('guest')->group(...)` wrapping GET `/login` pointing to `LoginController::class, 'create'`
  - Add `POST /login` → `LoginController::class, 'store'` named `login.attempt` (outside guest group so it can return errors)
  - Add `POST /logout` → `LoginController::class, 'destroy'` named `logout` with `auth` middleware
  - Add `Route::prefix('owner')->name('owner.')->middleware(['auth', 'owner'])->group(...)` with all 6 owner placeholder routes
  - Add `Route::prefix('admin')->name('admin.')->middleware(['auth', 'mosque', 'mosque.active'])->group(...)` with all 11 admin placeholder routes
  - Preserve all existing route names: `owner.dashboard`, `owner.mosques.index`, `owner.mosques.pending`, `owner.settings.index`, `owner.users.index`, `owner.reports.index`, `admin.dashboard`, `admin.mosque.edit`, `admin.schedules.index`, `admin.activities.index`, `admin.finance.income.index`, `admin.finance.expense.index`, `admin.finance.report.index`, `admin.announcements.index`, `admin.congregation.index`, `admin.staff.index`, `admin.donations.index`
  - _Requirements: 5.1, 5.2, 5.3, 6.1, 6.2, 6.5, 7.1, 7.2, 7.3, 7.7_

- [x] 5. Checkpoint — verify routes and login flow manually
  - Ensure all tests pass, ask the user if questions arise.
  - Run `php artisan route:list` and verify all named routes appear
  - Verify `php artisan serve` starts without errors

- [x] 6. Write feature tests
  - [x] 6.1 Create `tests/Feature/Auth/LoginTest.php`
    - Use `RefreshDatabase` trait
    - Create `CreatesTestMosque` trait in `tests/Traits/CreatesTestMosque.php` with helpers: `createSuperAdmin()`, `createActiveMosqueAdmin()`, `createStaffUser()`, `createUserWithNoRole()`
    - Test: `test_super_admin_is_redirected_to_owner_dashboard` — create super-admin, POST `/login`, assertRedirect to `owner.dashboard`
    - Test: `test_mosque_admin_is_redirected_to_admin_dashboard` — create mosque-admin with active mosque, POST `/login`, assertRedirect to `admin.dashboard`
    - Test: `test_staff_is_redirected_to_admin_dashboard` — create staff user with active mosque, POST `/login`, assertRedirect to `admin.dashboard`
    - Test: `test_wrong_password_returns_email_error` — POST with wrong password, assertSessionHasErrors('email'), assert error message is "Email atau password salah."
    - Test: `test_empty_form_returns_validation_errors` — POST with empty data, assertSessionHasErrors(['email', 'password'])
    - Test: `test_invalid_email_format_returns_error` — POST with non-email string, assertSessionHasErrors('email')
    - Test: `test_user_with_no_role_is_logged_out_with_error` — create user with no role, POST `/login`, assertGuest(), assertSessionHasErrors('email')
    - Test: `test_rate_limit_locks_after_five_attempts` — POST with wrong credentials 6 times, assert last response contains lockout message
    - Test: `test_session_is_regenerated_on_successful_login` — assert session ID differs before and after successful login
    - Test: `test_success_flash_is_set_on_successful_login` — login as super-admin, follow redirect, assertSessionHas('success')
    - Test: `test_authenticated_user_on_login_page_is_redirected` — actingAs super-admin, GET `/login`, assertRedirect
    - _Requirements: 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 3.1, 3.2, 3.5, 9.1, 9.2, 9.3, 9.4, 9.10_

  - [x] 6.2 Create `tests/Feature/Auth/LogoutTest.php`
    - Test: `test_authenticated_user_can_logout` — actingAs user, POST `/logout`, assertGuest(), assertRedirect to `login`
    - Test: `test_logout_redirects_to_login_with_flash` — POST `/logout`, follow redirect, assertSessionHas('success', 'Anda telah berhasil keluar.')
    - Test: `test_get_logout_route_is_not_allowed` — GET `/logout`, assert 405 Method Not Allowed
    - Test: `test_unauthenticated_user_cannot_post_logout` — POST `/logout` without authentication, assertRedirect to `login`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 9.5_

  - [x] 6.3 Create `tests/Feature/Auth/RouteProtectionTest.php`
    - Test: `test_unauthenticated_user_redirected_from_owner_dashboard` — GET `/owner/dashboard`, assertRedirect to `login`
    - Test: `test_mosque_admin_forbidden_on_owner_dashboard` — actingAs mosque-admin, GET `/owner/dashboard`, assertForbidden()
    - Test: `test_super_admin_can_access_owner_dashboard` — actingAs super-admin, GET `/owner/dashboard`, assertOk()
    - Test: `test_unauthenticated_user_redirected_from_admin_dashboard` — GET `/admin/dashboard`, assertRedirect to `login`
    - Test: `test_mosque_admin_with_active_mosque_can_access_admin_dashboard` — actingAs mosque-admin with active mosque (set `active_mosque_id`), GET `/admin/dashboard`, assertOk()
    - Test: `test_mosque_admin_with_suspended_mosque_is_forbidden_on_admin` — actingAs mosque-admin with suspended mosque, GET `/admin/dashboard`, assertForbidden()
    - Test: `test_super_admin_without_mosque_context_forbidden_on_admin` — actingAs super-admin with no `active_mosque_id`, GET `/admin/dashboard`, assertForbidden()
    - _Requirements: 6.3, 6.4, 6.6, 7.4, 7.5, 7.6, 9.6, 9.7, 9.8, 9.9_

- [x] 7. Final checkpoint — Ensure all tests pass
  - Run `php artisan test --filter=Auth` and verify all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- All paths are relative to `web/` within the monorepo
- `bootstrap/app.php` already has the correct middleware aliases — no changes needed there
- The login view (`resources/views/auth/login.blade.php`) already exists and already handles inline `$errors` display — no changes needed to the login view
- Owner and admin dashboard views already exist (`owner/dashboard.blade.php`, `admin/dashboard.blade.php`) — no changes needed
- No property-based tests are included — auth flow logic is best verified with specific example-based feature tests
- The `guest` middleware on GET `/login` relies on `App\Http\Middleware\RedirectIfAuthenticated` which Laravel provides out of the box; it uses the `home` config or redirects based on intended URL
- Rate limiting uses Laravel's built-in `RateLimiter` facade — no custom implementation needed
- The `CreatesTestMosque` trait should use `PermissionRegistrar::setPermissionsTeamId()` to correctly assign roles in tests, matching the pattern in `SuperAdminSeeder`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2"] },
    { "id": 2, "tasks": ["3", "4"] },
    { "id": 3, "tasks": ["6.1", "6.2", "6.3"] }
  ]
}
```
