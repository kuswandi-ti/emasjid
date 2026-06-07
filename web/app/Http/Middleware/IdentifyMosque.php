<?php

namespace App\Http\Middleware;

use App\Models\Mosque;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: IdentifyMosque
 *
 * Resolves the active mosque from:
 * 1. Route parameter {mosque} (explicit)
 * 2. User's active_mosque_id (session/DB)
 *
 * Binds the resolved mosque to the app container as 'current_mosque'
 * and sets the Spatie Permission team ID.
 */
class IdentifyMosque
{
    public function handle(Request $request, Closure $next): Response
    {
        $mosque = null;

        // 1. Try route parameter
        if ($request->route('mosque')) {
            $mosqueParam = $request->route('mosque');
            $mosque = $mosqueParam instanceof Mosque
                ? $mosqueParam
                : Mosque::find($mosqueParam);
        }

        // 2. Fallback to user's active mosque
        if (! $mosque && Auth::check()) {
            $user = Auth::user();
            if ($user->active_mosque_id) {
                $mosque = Mosque::find($user->active_mosque_id);
            }
        }

        // Bind to container
        if ($mosque) {
            app()->instance('current_mosque', $mosque);

            // Set Spatie Permission team (mosque_id) for permission checks
            app(PermissionRegistrar::class)->setPermissionsTeamId($mosque->id);
        }

        return $next($request);
    }
}
