<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ApproveMosqueRequest;
use App\Http\Requests\Owner\RejectMosqueRequest;
use App\Services\MosqueService;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Approve a pending mosque registration.
     *
     * Requirements: 1.2, 1.10, 1.11, 7.3, 7.4, 7.5
     */
    public function approve(ApproveMosqueRequest $request, int $id): RedirectResponse
    {
        $userId = auth()->id();

        try {
            $this->mosqueService->approve($id, $userId);

            return redirect()
                ->route('owner.mosques.show', $id)
                ->with('success', 'Masjid berhasil disetujui dan sekarang aktif di platform.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menyetujui masjid: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending mosque registration.
     *
     * Requirements: 2.3, 2.10, 2.11, 7.8, 7.9, 7.10
     */
    public function reject(RejectMosqueRequest $request, int $id): RedirectResponse
    {
        $rejectionReason = $request->validated()['rejection_reason'];
        $userId = auth()->id();

        try {
            $this->mosqueService->reject($id, $rejectionReason, $userId);

            return redirect()
                ->route('owner.mosques.pending')
                ->with('success', 'Masjid berhasil ditolak dan admin masjid telah diberitahu.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menolak masjid: ' . $e->getMessage());
        }
    }
}
