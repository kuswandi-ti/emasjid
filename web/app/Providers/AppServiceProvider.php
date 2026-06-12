<?php

namespace App\Providers;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super-admin bypasses all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Provide $pendingCount to the owner sidebar so the badge is always up-to-date.
        // The composer runs only when the sidebar partial is rendered, keeping the
        // query isolated to owner panel requests.
        View::composer('owner.partials.sidebar', function ($view) {
            $pendingCount = Mosque::where('status', MosqueStatus::Pending->value)->count();
            $view->with('pendingCount', $pendingCount);
        });
    }
}
