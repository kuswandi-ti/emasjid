<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display owner dashboard with platform statistics.
     */
    public function index(): View
    {
        $statistics = $this->dashboardService->getStatistics();

        return view('owner.dashboard', compact('statistics'));
    }
}
