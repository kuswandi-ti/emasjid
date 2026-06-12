# Requirements Document

## Introduction

This feature covers Day 5 of the EMasjid platform: wiring the authentication system for the web panels. The existing login view, middleware classes, and placeholder routes are already in place. This spec defines the requirements for the `LoginController`, `LoginRequest`, middleware registration in `bootstrap/app.php`, protected route groups for owner and admin panels, and the corresponding feature tests.

The authentication system uses Laravel's built-in session auth (web guard) with Spatie laravel-permission (teams mode) for role-based redirect. There are two distinct panels:

- **Owner Panel** (`/owner`) — for `super-admin` role (platform-level, `mosque_id = 0`)
- **Admin Panel** (`/admin`) — for `mosque-admin` and `staff` roles (mosque-scoped)

All paths in this document are relative to `web/` unless explicitly stated otherwise.

## Glossary

- **Login_Controller**: The `App\Http\Controllers\Auth\LoginController` class that handles login form submission, credential verification, role-based redirect, and logout
- **Login_Request**: The `App\Http\Requests\Auth\LoginRequest` form request that validates login form input
- **Auth_Guard**: Laravel's `web` session-based authentication guard
- **Route_Protector**: The combination of `auth` middleware and panel-specific middleware that restricts access to authenticated users with the correct role
- **Owner_Panel**: The web panel at `/owner` for super-admin users, protected by `EnsureOwnerAccess` middleware
- **Admin_Panel**: The web panel at `/admin` for mosque-admin and staff users, protected by `IdentifyMosque` and `EnsureMosqueActive` middleware
- **Flash_Session**: A Laravel one-time session value used to pass success or error messages to the next request for display via SweetAlert2
- **Role_Redirector**: The logic within `Login_Controller` that inspects the authenticated user's role and issues the appropriate redirect response

## Requirements

### Requirement 1: Login Form Validation

**User Story:** As a user, I want the login form to validate my input before attempting authentication, so that I receive clear feedback when I submit an incomplete or malformed form.

#### Acceptance Criteria

1. THE Login_Request SHALL declare the `email` field as required with the `email` validation rule
2. THE Login_Request SHALL declare the `password` field as required with a minimum length of 8 characters
3. THE Login_Request SHALL declare the `remember` field as optional with a boolean validation rule
4. WHEN the `email` field is missing or empty, THE Login_Request SHALL return a validation error for the `email` field
5. WHEN the `email` field contains a non-email string, THE Login_Request SHALL return a validation error for the `email` field
6. WHEN the `password` field is missing or has fewer than 8 characters, THE Login_Request SHALL return a validation error for the `password` field
7. WHEN validation fails, THE Login_Controller SHALL redirect back to the login page with error messages and the previously submitted `email` value preserved via `old('email')`

### Requirement 2: Successful Authentication and Role-Based Redirect

**User Story:** As a platform owner or mosque admin, I want to be redirected to the correct dashboard after login, so that I land directly in the panel that is relevant to my role.

#### Acceptance Criteria

1. WHEN a user submits valid credentials for a `super-admin` account, THE Login_Controller SHALL authenticate the user and redirect to the `owner.dashboard` route
2. WHEN a user submits valid credentials for a `mosque-admin` account, THE Login_Controller SHALL authenticate the user and redirect to the `admin.dashboard` route
3. WHEN a user submits valid credentials for a `staff` account, THE Login_Controller SHALL authenticate the user and redirect to the `admin.dashboard` route
4. WHEN authenticating a `super-admin` user, THE Login_Controller SHALL check the role using Spatie permission with `mosque_id = 0` as the team context (platform-level role)
5. WHEN a user is authenticated but has no recognized role (`super-admin`, `mosque-admin`, or `staff`), THE Login_Controller SHALL log the user out and redirect to the login page with an error message "Akun Anda tidak memiliki akses ke panel ini."
6. WHEN authentication succeeds, THE Login_Controller SHALL call `session()->regenerate()` before issuing the redirect to prevent session fixation attacks
7. WHEN authentication succeeds, THE Login_Controller SHALL set a `success` flash message "Selamat datang, {name}!" on the session before redirecting

### Requirement 3: Failed Authentication

**User Story:** As a user, I want to receive an informative error message when my credentials are wrong, so that I know the login attempt failed without exposing which field was incorrect.

#### Acceptance Criteria

1. WHEN a user submits an email that does not exist in the database, THE Login_Controller SHALL not authenticate the user and SHALL add an error "Email atau password salah." to the `email` field errors
2. WHEN a user submits a correct email but incorrect password, THE Login_Controller SHALL not authenticate the user and SHALL add an error "Email atau password salah." to the `email` field errors
3. WHEN authentication fails, THE Login_Controller SHALL preserve the submitted `email` value in the response so it repopulates the form field via `old('email')`
4. WHEN authentication fails, THE Login_Controller SHALL NOT disclose which specific field (email or password) caused the failure
5. IF a user submits more than 5 failed login attempts within 1 minute for the same email, THEN THE Login_Controller SHALL lock further attempts and return an error "Terlalu banyak percobaan login. Coba lagi dalam 1 menit." using Laravel's built-in throttle mechanism

### Requirement 4: Logout

**User Story:** As an authenticated user, I want to be able to log out cleanly, so that my session is fully terminated and I am returned to the login page.

#### Acceptance Criteria

1. WHEN a user sends a `POST` request to the `logout` route, THE Login_Controller SHALL call `Auth::logout()`
2. WHEN logging out, THE Login_Controller SHALL call `session()->invalidate()` to clear all session data
3. WHEN logging out, THE Login_Controller SHALL call `session()->regenerateToken()` to issue a new CSRF token
4. AFTER a successful logout, THE Login_Controller SHALL redirect to the `login` route with a `success` flash message "Anda telah berhasil keluar."
5. THE `logout` route SHALL be protected by the `POST` method and a CSRF token so that it cannot be triggered via a simple GET link

### Requirement 5: Middleware Registration

**User Story:** As a developer, I want all custom middleware classes registered and aliased in `bootstrap/app.php`, so that they can be referenced by alias in route definitions.

#### Acceptance Criteria

1. THE `bootstrap/app.php` SHALL register `IdentifyMosque` with the alias `mosque`
2. THE `bootstrap/app.php` SHALL register `EnsureMosqueActive` with the alias `mosque.active`
3. THE `bootstrap/app.php` SHALL register `EnsureOwnerAccess` with the aliases `mosque.ownership` and `owner`
4. WHEN a route applies the `owner` alias, THE Route_Protector SHALL deny access to users who do not have the `super-admin` role and redirect to the login page
5. WHEN a route applies the `mosque` alias, THE Route_Protector SHALL resolve and bind the current mosque to the application container
6. WHEN a route applies the `mosque.active` alias after `mosque`, THE Route_Protector SHALL return HTTP 403 if the resolved mosque is not in `active` status

### Requirement 6: Protected Owner Panel Routes

**User Story:** As the platform owner, I want all `/owner` routes protected by authentication and role checking, so that only super-admin users can access the owner panel.

#### Acceptance Criteria

1. THE `owner.*` route group SHALL apply the `auth` middleware to require an authenticated session
2. THE `owner.*` route group SHALL apply the `owner` middleware alias to verify the `super-admin` role
3. WHEN an unauthenticated user accesses any `/owner` URL, THE Route_Protector SHALL redirect to the `login` route
4. WHEN an authenticated user without `super-admin` role accesses any `/owner` URL, THE Route_Protector SHALL return HTTP 403 with the message "Akses ditolak. Hanya super admin yang dapat mengakses halaman ini."
5. THE owner route group SHALL define named routes: `owner.dashboard`, `owner.mosques.index`, `owner.mosques.pending`, `owner.settings.index`, `owner.users.index`, `owner.reports.index`
6. WHEN a `super-admin` user visits `/owner/dashboard`, THE Route_Protector SHALL permit access and render the owner dashboard view

### Requirement 7: Protected Admin Panel Routes

**User Story:** As a mosque admin or staff member, I want all `/admin` routes protected so that only authenticated users with a valid active mosque context can access the admin panel.

#### Acceptance Criteria

1. THE `admin.*` route group SHALL apply the `auth` middleware to require an authenticated session
2. THE `admin.*` route group SHALL apply the `mosque` middleware alias to resolve the current mosque
3. THE `admin.*` route group SHALL apply the `mosque.active` middleware alias to verify the mosque is active
4. WHEN an unauthenticated user accesses any `/admin` URL, THE Route_Protector SHALL redirect to the `login` route
5. WHEN an authenticated user whose mosque is not in `active` status accesses any `/admin` URL, THE Route_Protector SHALL return HTTP 403 with the appropriate status message
6. WHEN an authenticated `mosque-admin` user with an active mosque visits `/admin/dashboard`, THE Route_Protector SHALL permit access and render the admin dashboard view
7. THE admin route group SHALL define named routes: `admin.dashboard`, `admin.mosque.edit`, `admin.schedules.index`, `admin.activities.index`, `admin.finance.income.index`, `admin.finance.expense.index`, `admin.finance.report.index`, `admin.announcements.index`, `admin.congregation.index`, `admin.staff.index`, `admin.donations.index`

### Requirement 8: SweetAlert2 Flash Message Integration

**User Story:** As a user, I want success and error messages displayed via SweetAlert2 toast notifications, so that I receive clear, consistent visual feedback after authentication actions.

#### Acceptance Criteria

1. WHEN the session contains a `success` key, THE Auth_Guard SHALL display a SweetAlert2 toast notification with `icon: 'success'` in the redirected view
2. WHEN the session contains an `error` key, THE Auth_Guard SHALL display a SweetAlert2 toast notification with `icon: 'error'` in the redirected view
3. THE flash message display logic SHALL be implemented in a Blade layout or shared component so it applies automatically to all views without per-view code
4. WHEN the login form validation fails and errors are returned, THE login view SHALL display the first validation error inline within the form (not via SweetAlert2 toast) using a Bootstrap `alert-danger` component
5. THE SweetAlert2 toast notifications SHALL use `position: 'top-end'` and `timer: 3000` for non-blocking auto-dismiss behavior

### Requirement 9: Feature Tests

**User Story:** As a developer, I want automated feature tests for the login flow, so that regressions in authentication behavior are caught early.

#### Acceptance Criteria

1. THE test suite SHALL include a test that verifies a valid `super-admin` user is redirected to `owner.dashboard` after login
2. THE test suite SHALL include a test that verifies a valid `mosque-admin` user is redirected to `admin.dashboard` after login
3. THE test suite SHALL include a test that verifies an incorrect password returns a validation error with the message "Email atau password salah."
4. THE test suite SHALL include a test that verifies submitting an empty form returns validation errors for `email` and `password`
5. THE test suite SHALL include a test that verifies a logout request invalidates the session and redirects to the login page
6. THE test suite SHALL include a test that verifies an unauthenticated user is redirected to `login` when accessing `/owner/dashboard`
7. THE test suite SHALL include a test that verifies an authenticated `mosque-admin` user is forbidden (HTTP 403) when accessing `/owner/dashboard`
8. THE test suite SHALL include a test that verifies an unauthenticated user is redirected to `login` when accessing `/admin/dashboard`
9. THE test suite SHALL include a test that verifies a `mosque-admin` user whose mosque is suspended receives HTTP 403 when accessing `/admin/dashboard`
10. IF a login attempt exceeds the rate limit threshold, THEN THE test suite SHALL verify that a lockout error message is returned
