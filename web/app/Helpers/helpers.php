<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('mosque_id')) {
    /**
     * Get the current active mosque ID from the authenticated user.
     *
     * Returns null if no user is authenticated or no active mosque is set.
     */
    function mosque_id(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->active_mosque_id;
    }
}
