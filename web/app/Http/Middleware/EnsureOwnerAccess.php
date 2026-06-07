<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureOwnerAccess
 *
 * Ensures the authenticated user has the 'super-admin' role.
 * Used to protect the Owner Panel (/owner) routes.
 *
 * Super-admin is a platform-level role (mosque_id = 0).
 */
class EnsureOwnerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check super-admin role in platform context (team_id = 0)
        $registrar = app(PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(0);

        $isSuperAdmin = $user->hasRole('super-admin');

        // Restore original team context
        $registrar->setPermissionsTeamId($originalTeamId);

        if (! $isSuperAdmin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya super admin yang dapat mengakses halaman ini.',
                ], 403);
            }

            abort(403, 'Akses ditolak. Hanya super admin yang dapat mengakses halaman ini.');
        }

        return $next($request);
    }
}
