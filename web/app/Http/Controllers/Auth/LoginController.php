<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class LoginController extends Controller
{
    /**
     * Display the login view.
     * Redirects authenticated users to their appropriate dashboard.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $redirect = $this->resolveRedirect();

            return redirect($redirect ?? route('login'));
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * 1. Delegates validation + throttle-checking + Auth::attempt to LoginRequest
     * 2. Regenerates the session to prevent session fixation
     * 3. Determines the post-login redirect based on the user's role
     * 4. Logs out and returns an error if no recognized role is found
     */
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
            ->with('success', 'Selamat datang, '.Auth::user()->name.'!');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Resolve the post-login redirect URL based on the authenticated user's role.
     *
     * Checks for super-admin at team_id = 0 (platform level), then checks
     * mosque-admin or staff at any team scope. Returns null if no recognized role.
     */
    private function resolveRedirect(): ?string
    {
        $user = Auth::user();
        $registrar = app(PermissionRegistrar::class);

        // Check platform-level super-admin (team_id = 0)
        $registrar->setPermissionsTeamId(0);
        if ($user->hasRole('super-admin')) {
            return route('owner.dashboard');
        }

        // Check mosque-level roles using the user's active mosque as team context.
        // We must unset the cached 'roles' relationship so it is re-queried with
        // the new team ID (the relationship query uses getPermissionsTeamId()).
        if ($user->active_mosque_id) {
            $registrar->setPermissionsTeamId($user->active_mosque_id);
        } else {
            $registrar->setPermissionsTeamId(null);
        }
        $user->unsetRelation('roles');

        if ($user->hasRole('mosque-admin') || $user->hasRole('staff')) {
            return route('admin.dashboard');
        }

        return null; // No recognized role
    }
}
