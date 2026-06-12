<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\MosqueService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MosqueController extends Controller
{
    public function __construct(
        protected MosqueService $mosqueService
    ) {}

    /**
     * Display all mosques with filtering and pagination.
     *
     * Requirements: 3.1, 3.2, 3.3, 3.4
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'search', 'city']);
        $perPage = $request->input('per_page', 15);

        $mosques = $this->mosqueService->getMosqueList($filters, $perPage);

        return view('owner.mosques.index', compact('mosques', 'filters'));
    }

    /**
     * Display pending mosques queue.
     *
     * Requirements: 4.1, 4.5
     */
    public function pending(Request $request): View
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $mosques = $this->mosqueService->getPendingMosques($search, $perPage);

        return view('owner.mosques.pending', compact('mosques'));
    }

    /**
     * Display detailed mosque information.
     *
     * Requirements: 5.1, 5.3
     */
    public function show(int $id): View
    {
        $mosque = $this->mosqueService->getMosqueDetail($id);

        return view('owner.mosques.show', compact('mosque'));
    }
}
