<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnerRequest;
use App\Http\Requests\Owner\UpdateOwnerRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Tampilkan daftar akun owner (DataTable server-side) atau kembalikan JSON.
     *
     * Requirements: 1.1, 1.2, 1.4
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->userService->getOwnerQueryBuilder();

            return DataTables::of($query)
                ->addColumn('action', fn (User $user) => view('owner.users._action', compact('user'))->render())
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('owner.users.index');
    }

    /**
     * Tampilkan form tambah akun owner baru.
     *
     * Requirements: 2.1
     */
    public function create(): View
    {
        return view('owner.users.create');
    }

    /**
     * Simpan akun owner baru ke database.
     *
     * Requirements: 2.2, 2.3, 2.4, 2.5
     */
    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        try {
            $this->userService->createOwner($request->validated());

            return redirect()
                ->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil ditambahkan.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan form edit akun owner.
     *
     * Requirements: 3.1
     */
    public function edit(User $user): View
    {
        return view('owner.users.edit', compact('user'));
    }

    /**
     * Perbarui data akun owner.
     *
     * Requirements: 3.2, 3.3, 3.4, 3.5, 3.6, 3.7
     */
    public function update(UpdateOwnerRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->updateOwner($user, $request->validated(), Auth::id());

            return redirect()
                ->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil diperbarui.');
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Hapus akun owner dari database (soft delete).
     *
     * Requirements: 4.1, 4.3, 4.4, 4.5
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->userService->deleteOwner($user, Auth::id());

            return redirect()
                ->route('owner.users.index')
                ->with('success', 'Akun Admin Platform berhasil dihapus.');
        } catch (\DomainException $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }
}
