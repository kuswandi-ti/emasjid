# Design Document: Day 5 — Login & Auth Wiring (Web)

## Overview

This design covers wiring the authentication layer for the EMasjid web panels. The existing building blocks — login view, layout files, shared components, middleware classes, and placeholder routes — are already in place. This document specifies the three new files to create and the two existing files to modify in order to produce a fully functional, role-aware login/logout flow with proper route protection.

The Laravel project lives at `web/` within the monorepo. All paths in this document are relative to `web/` unless explicitly stated otherwise.

### Key Design Decisions

1. **No service layer for authentication** — Laravel's built-in `Auth::attempt()` is sufficient for simple credential checking. Injecting a service layer here would add indirection without benefit.
2. **Role check via Spatie Permission with team scope** — `super-admin` is assigned at `mosque_id = 0` (platform level). The `Login_Controller` temporarily sets the Spatie team ID to 0 to check for this role, then relies on `EnsureOwnerAccess` middleware for subsequent requests.
3. **FormRequest for validation** — `LoginRequest` handles all validation rules and rate-limiting throttle check, keeping the controller thin.
4. **Flash session → SweetAlert2** — Success/error feedback uses Laravel's `with()` helper to flash a session key, consumed by the shared layout's inline JavaScript block.
5. **Single login route for both panels** — Both owner and admin users land on the same `/login` page and are redirected post-login based on role. No separate login endpoints.
6. **Middleware aliases already registered** — The `bootstrap/app.php` already contains the middleware aliases (`mosque`, `mosque.active`, `mosque.ownership`, `owner`). The task is to ensure they are correct and used in route groups.

---

## Architecture

```
POST /login
  └── LoginRequest (validate + throttle)
      └── LoginController@login
            ├── Auth::attempt()
            ├── role check (Spatie, team_id=0 for super-admin)
            └── redirect with flash
                  ├── super-admin → /owner/dashboard
                  ├── mosque-admin / staff → /admin/dashboard
                  └── no role → logout + redirect to /login

POST /logout
  └── auth middleware
      └── LoginController@logout
            └── Auth::logout() + session invalidate + redirect to /login

Route::prefix('owner') + ['auth', 'owner']
Route::prefix('admin') + ['auth', 'mosque', 'mosque.active']
```

### Sequence Flows

#### Successful Owner Login

```
Browser → POST /login (email, password)
  → LoginRequest validates fields
  → LoginController@login
      → Auth::attempt(['email', 'password'], remember)
      → setPermissionsTeamId(0), user->hasRole('super-admin')
      → session()->regenerate()
      → flash success "Selamat datang, {name}!"
      → redirect owner.dashboard
  → EnsureOwnerAccess middleware (subsequent requests)
      → checks super-admin role at team_id=0
      → allows through
```

#### Successful Admin Login

```
Browser → POST /login (email, password)
  → LoginRequest validates fields
  → LoginController@login
      → Auth::attempt(['email', 'password'], remember)
      → user->hasRole('mosque-admin') OR user->hasRole('staff')
      → session()->regenerate()
      → flash success "Selamat datang, {name}!"
      → redirect admin.dashboard
  → IdentifyMosque middleware (subsequent requests)
      → resolves mosque from user->active_mosque_id
      → binds to container + sets team ID
  → EnsureMosqueActive middleware
      → checks mosque->status === MosqueStatus::Active
      → allows through
```

#### Failed Login

```
Browser → POST /login (bad credentials)
  → LoginController@login
      → Auth::attempt() returns false
      → RateLimiter::hit()
      → withErrors(['email' => 'Email atau password salah.'])
      → redirect back with old input
```

#### Logout

```
Browser → POST /logout (with CSRF token)
  → auth middleware (requires authenticated session)
  → LoginController@logout
      → Auth::logout()
      → session()->invalidate()
      → session()->regenerateToken()
      → redirect login with flash "Anda telah berhasil keluar."
```

---

## Components and Interfaces

### 1. `LoginRequest` — `app/Http/Requests/Auth/LoginRequest.php`

```php
namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool;

    public function rules(): array;
    // returns:
    // 'email'    => ['required', 'string', 'email']
    // 'password' => ['required', 'string', 'min:8']
    // 'remember' => ['boolean']

    /**
     * Throttle key based on email + IP, max 5 attempts per minute.
     */
    public function throttleKey(): string;

    /**
     * Ensure the login request is not rate limited.
     * Throws ValidationException if locked out.
     */
    public function ensureIsNotRateLimited(): void;

    /**
     * Attempt to authenticate the request's credentials.
     * Increments RateLimiter on failure.
     * Throws ValidationException on failure.
     */
    public function authenticate(): void;
}
```

**Throttle strategy:** The key is `Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip())`. Max 5 attempts, decay 60 seconds. This follows the Laravel Breeze pattern exactly.

### 2. `LoginController` — `app/Http/Controllers/Auth/LoginController.php`

```php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class LoginController extends Controller
{
    /**
     * Display the login view.
     * Redirects authenticated users to their dashboard.
     */
    public function create(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse;

    /**
     * Handle an incoming authentication request.
     * 1. Delegate authentication to LoginRequest (validates + throttle-checks)
     * 2. Regenerate session
     * 3. Determine redirect based on role
     * 4. Flash success message
     */
    public function store(LoginRequest $request): RedirectResponse;

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse;

    /**
     * Resolve the post-login redirect route based on user roles.
     * Checks super-admin at team_id=0, then checks mosque-admin/staff.
     * Logs out and returns null if no recognized role.
     */
    private function resolveRedirect(): ?string;
}
```

**Role resolution logic:**

```php
private function resolveRedirect(): ?string
{
    $user = Auth::user();
    $registrar = app(PermissionRegistrar::class);

    // Check platform-level super-admin (team_id = 0)
    $registrar->setPermissionsTeamId(0);
    if ($user->hasRole('super-admin')) {
        return route('owner.dashboard');
    }

    // Check mosque-level roles (team_id not constrained here — any mosque)
    // hasRole() without team context uses the current team ID.
    // We reset to null to allow Spatie to find the role in any team.
    $registrar->setPermissionsTeamId(null);
    if ($user->hasRole('mosque-admin') || $user->hasRole('staff')) {
        return route('admin.dashboard');
    }

    return null; // No recognized role
}
```

**Controller store() method:**

```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate(); // validates + throttle + Auth::attempt

    $request->session()->regenerate();

    $redirect = $this->resolveRedirect();

    if ($redirect === null) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withErrors(['email' => 'Akun Anda tidak memiliki akses ke panel ini.']);
    }

    return redirect()->intended($redirect)
        ->with('success', 'Selamat datang, ' . Auth::user()->name . '!');
}
```

### 3. `bootstrap/app.php` — Middleware Registration (already done, verify)

The file already contains:

```php
$middleware->alias([
    'mosque'           => IdentifyMosque::class,
    'mosque.active'    => EnsureMosqueActive::class,
    'mosque.ownership' => EnsureOwnerAccess::class,
    'owner'            => EnsureOwnerAccess::class,
]);
```

No changes needed to `bootstrap/app.php`. The aliases are already registered correctly.

### 4. `routes/web.php` — Replace Placeholder Routes with Protected Groups

The existing file has closure-based placeholder routes without middleware. This must be replaced with proper controller routes and middleware groups.

**Complete new `routes/web.php`:**

```php
<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// ─── Root redirect ────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── Auth routes (guest only) ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
});

Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')
    ->middleware('auth');

// ─── Owner panel ──────────────────────────────────────────────────────────────
Route::prefix('owner')->name('owner.')->middleware(['auth', 'owner'])->group(function () {
    Route::get('/dashboard',       fn () => view('owner.dashboard'))->name('dashboard');
    Route::get('/mosques',         fn () => view('owner.dashboard'))->name('mosques.index');
    Route::get('/mosques/pending', fn () => view('owner.dashboard'))->name('mosques.pending');
    Route::get('/settings',        fn () => view('owner.dashboard'))->name('settings.index');
    Route::get('/users',           fn () => view('owner.dashboard'))->name('users.index');
    Route::get('/reports',         fn () => view('owner.dashboard'))->name('reports.index');
});

// ─── Admin panel ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'mosque', 'mosque.active'])->group(function () {
    Route::get('/dashboard',               fn () => view('admin.dashboard'))->name('dashboard');
    Route::get('/mosque/edit',             fn () => view('admin.dashboard'))->name('mosque.edit');
    Route::get('/schedules',               fn () => view('admin.dashboard'))->name('schedules.index');
    Route::get('/activities',              fn () => view('admin.dashboard'))->name('activities.index');
    Route::get('/finance/income',          fn () => view('admin.dashboard'))->name('finance.income.index');
    Route::get('/finance/expense',         fn () => view('admin.dashboard'))->name('finance.expense.index');
    Route::get('/finance/report',          fn () => view('admin.dashboard'))->name('finance.report.index');
    Route::get('/announcements',           fn () => view('admin.dashboard'))->name('announcements.index');
    Route::get('/congregation',            fn () => view('admin.dashboard'))->name('congregation.index');
    Route::get('/staff',                   fn () => view('admin.dashboard'))->name('staff.index');
    Route::get('/donations',               fn () => view('admin.dashboard'))->name('donations.index');
});
```

**Design notes on route naming:**
- The login form `action` points to the `POST` route. We use `login.attempt` for the POST action to keep it distinct from the GET `login` route (the named `login` route is the GET view, per Laravel convention expected by the `auth` middleware's redirect).
- The `guest` middleware on GET `/login` prevents authenticated users from seeing the login form — they are redirected to their dashboard.
- Placeholder closure views remain for routes not yet fully implemented (Days 7–19 will replace them).

### 5. Flash Message Integration in Layouts

The existing layouts (`layouts/owner.blade.php`, `layouts/admin.blade.php`, `layouts/auth.blade.php`) need a shared flash message script block. This should be added to `layouts/app.blade.php` or as a shared component so it applies universally.

**Approach:** Add an `@include` of a `components/flash-messages.blade.php` (or inline script block) in `layouts/app.blade.php` after the `@stack('scripts')` push. The component checks for `session('success')` and `session('error')` and emits a SweetAlert2 call.

```blade
{{-- resources/views/components/flash-messages.blade.php --}}
@if (session('success') || session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: '{{ session('success') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        @endif
        @if (session('error'))
        Swal.fire({
            icon: 'error',
            title: '{{ session('error') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        @endif
    });
</script>
@endif
```

The login view already handles inline `$errors` with a Bootstrap `alert-danger` block — that stays unchanged.

---

## Data Models

No new database tables or model changes are required for this feature. The relevant existing models and their key fields used in auth logic are:

### `User` (existing)

| Field | Type | Role in Auth |
|-------|------|-------------|
| `email` | string | Credential lookup |
| `password` | hashed string | Credential verification |
| `name` | string | Flash message personalization |
| `active_mosque_id` | nullable FK | Resolved by `IdentifyMosque` to bind current mosque |

### `Mosque` (existing)

| Field | Type | Role in Auth |
|-------|------|-------------|
| `id` | bigint | Team ID for Spatie permission scope |
| `status` | MosqueStatus enum | Checked by `EnsureMosqueActive` |

### Spatie Permission Tables (existing)

| Table | Role in Auth |
|-------|-------------|
| `roles` | `super-admin`, `mosque-admin`, `staff` roles |
| `model_has_roles` | Pivot with `team_id` (`mosque_id` or `0` for platform) |

---

## Error Handling

### Authentication Errors

| Scenario | Handling |
|----------|----------|
| Wrong credentials | `withErrors(['email' => 'Email atau password salah.'])`, redirect back with `old('email')` |
| Rate limit exceeded | `ValidationException` with lockout message, redirect back |
| No recognized role after auth | `Auth::logout()`, redirect to login with generic error |
| Unauthenticated on protected route | `auth` middleware redirects to `login` route |
| Non-super-admin on `/owner` | `EnsureOwnerAccess` aborts with HTTP 403 |
| Inactive mosque on `/admin` | `EnsureMosqueActive` aborts with HTTP 403 + status-specific message |

### Route Guard Errors

| Scenario | HTTP Response |
|----------|---------------|
| Unauthenticated user → `/owner/*` | 302 → `/login` |
| Unauthenticated user → `/admin/*` | 302 → `/login` |
| `mosque-admin` → `/owner/*` | 403 "Akses ditolak. Hanya super admin yang dapat mengakses halaman ini." |
| `super-admin` → `/admin/*` (no mosque) | 403 "Masjid tidak ditemukan. Silakan pilih masjid terlebih dahulu." |
| Valid user, suspended mosque → `/admin/*` | 403 "Masjid sedang ditangguhkan. Hubungi admin platform." |

### Session Errors

| Scenario | Handling |
|----------|----------|
| CSRF mismatch on `/logout` | Laravel default: 419 Page Expired |
| GET request to `/logout` | Route not found (405 Method Not Allowed) |

---

## Testing Strategy

Property-based testing is **not applicable** for this feature. The work consists of:

- Controller redirect logic (role-conditional, specific named routes)
- Middleware access control (specific role/status checks)
- Session management (regenerate, invalidate)
- Route registration (static configuration)

These behaviors are best verified with concrete examples using specific users, roles, and mosque states.

### Testing Approach

**Unit Tests** — not needed for this feature since controller logic is minimal and redirect behavior is best verified end-to-end.

**Feature Tests** — all tests live in `tests/Feature/Auth/` and use Laravel's `RefreshDatabase` trait. Tests use `actingAs()` and the `Illuminate\Testing\TestResponse` assertions (`assertRedirect`, `assertSessionHasErrors`, `assertForbidden`, etc.).

### Test File Organization

```
tests/Feature/Auth/
├── LoginTest.php          # Login happy path, validation, throttle
├── LogoutTest.php         # Logout flow
└── RouteProtectionTest.php # Middleware access control for owner + admin panels
```

### Test Cases

#### `LoginTest.php`

| # | Test method | Requirement |
|---|-------------|-------------|
| 1 | `test_super_admin_is_redirected_to_owner_dashboard` | 2.1 |
| 2 | `test_mosque_admin_is_redirected_to_admin_dashboard` | 2.2 |
| 3 | `test_staff_is_redirected_to_admin_dashboard` | 2.3 |
| 4 | `test_wrong_password_returns_email_error` | 3.1, 3.2, 3.4 |
| 5 | `test_empty_form_returns_validation_errors` | 1.4, 1.6 |
| 6 | `test_invalid_email_format_returns_error` | 1.5 |
| 7 | `test_user_with_no_role_is_logged_out_with_error` | 2.5 |
| 8 | `test_rate_limit_locks_after_five_attempts` | 3.5 |
| 9 | `test_session_is_regenerated_on_successful_login` | 2.6 |
| 10 | `test_success_flash_is_set_on_successful_login` | 2.7 |
| 11 | `test_authenticated_user_on_login_page_is_redirected` | guest middleware |

#### `LogoutTest.php`

| # | Test method | Requirement |
|---|-------------|-------------|
| 1 | `test_authenticated_user_can_logout` | 4.1, 4.2, 4.3, 4.4 |
| 2 | `test_logout_redirects_to_login_with_flash` | 4.4 |
| 3 | `test_get_logout_route_is_not_allowed` | 4.5 |
| 4 | `test_unauthenticated_user_cannot_access_logout` | 4.5 |

#### `RouteProtectionTest.php`

| # | Test method | Requirement |
|---|-------------|-------------|
| 1 | `test_unauthenticated_user_redirected_from_owner_dashboard` | 6.3, 9.6 |
| 2 | `test_mosque_admin_forbidden_on_owner_dashboard` | 6.4, 9.7 |
| 3 | `test_super_admin_can_access_owner_dashboard` | 6.6 |
| 4 | `test_unauthenticated_user_redirected_from_admin_dashboard` | 7.4, 9.8 |
| 5 | `test_mosque_admin_with_active_mosque_can_access_admin_dashboard` | 7.6 |
| 6 | `test_mosque_admin_with_suspended_mosque_is_forbidden_on_admin` | 7.5, 9.9 |
| 7 | `test_super_admin_without_mosque_context_forbidden_on_admin` | 7.5 |

### Test Fixtures

Feature tests require:

- A `super-admin` user created with role assigned at `team_id = 0`
- A `mosque-admin` user with a mosque in `active` status, `active_mosque_id` set
- A `staff` user with role assigned at the mosque's team ID
- A `mosque-admin` user with a mosque in `suspended` status
- A user with no role assigned

These should be set up in each test's `setUp()` or inline using model factories + `SuperAdminSeeder` pattern.

Example setup helper pattern (shared trait or base class):

```php
// tests/Traits/CreatesTestMosque.php (already referenced in AGENTS.md)
trait CreatesTestMosque
{
    protected function createActiveAdminUser(): User { ... }
    protected function createSuperAdmin(): User { ... }
    protected function createSuspendedMosque(): Mosque { ... }
}
```
