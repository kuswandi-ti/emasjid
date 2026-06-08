<?php

namespace App\Repositories;

use App\Contracts\Repositories\StaffRepositoryInterface;
use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::where('mosque_id', $mosqueId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['position'])) {
            $query->where('position', 'like', '%' . $filters['position'] . '%');
        }

        return $query->with(['user', 'mosque'])->latest()->paginate($perPage);
    }

    public function find(int $id): ?Staff
    {
        return Staff::with(['user', 'mosque'])->find($id);
    }

    public function findByUser(int $mosqueId, int $userId): ?Staff
    {
        return Staff::where('mosque_id', $mosqueId)
            ->where('user_id', $userId)
            ->with(['user', 'mosque'])
            ->first();
    }

    public function create(array $data): Staff
    {
        return Staff::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Staff::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Staff::where('id', $id)->delete();
    }

    public function getActive(int $mosqueId): Collection
    {
        return Staff::where('mosque_id', $mosqueId)
            ->where('is_active', true)
            ->with('user')
            ->orderBy('position')
            ->get();
    }

    public function getByPosition(int $mosqueId, string $position): Collection
    {
        return Staff::where('mosque_id', $mosqueId)
            ->where('position', $position)
            ->with('user')
            ->get();
    }

    public function activate(int $id): bool
    {
        return Staff::where('id', $id)->update(['is_active' => true]);
    }

    public function deactivate(int $id): bool
    {
        return Staff::where('id', $id)->update(['is_active' => false]);
    }

    public function isStaff(int $mosqueId, int $userId): bool
    {
        return Staff::where('mosque_id', $mosqueId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getUserStaffMosques(int $userId): Collection
    {
        return Staff::where('user_id', $userId)
            ->with('mosque')
            ->get()
            ->pluck('mosque');
    }
}
