<?php

namespace App\Repositories;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Models\Mosque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MosqueRepository implements MosqueRepositoryInterface
{
    public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Mosque::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (isset($filters['province'])) {
            $query->where('province', $filters['province']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('address', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('city', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with('admin')->latest()->paginate($perPage);
    }

    public function find(int $id): ?Mosque
    {
        return Mosque::with('admin')->find($id);
    }

    public function findBySlug(string $slug): ?Mosque
    {
        return Mosque::where('slug', $slug)->with('admin')->first();
    }

    public function findByInvitationCode(string $code): ?Mosque
    {
        return Mosque::where('invitation_code', $code)->first();
    }

    public function create(array $data): Mosque
    {
        return Mosque::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Mosque::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Mosque::where('id', $id)->delete();
    }

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Mosque::where('status', $status)
            ->with('admin')
            ->latest()
            ->paginate($perPage);
    }

    public function getByCity(string $city, int $perPage = 15): LengthAwarePaginator
    {
        return Mosque::where('city', $city)
            ->where('status', 'active')
            ->with('admin')
            ->latest()
            ->paginate($perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Mosque::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('address', 'like', '%' . $query . '%')
                    ->orWhere('city', 'like', '%' . $query . '%')
                    ->orWhere('province', 'like', '%' . $query . '%');
            })
            ->with('admin')
            ->latest()
            ->paginate($perPage);
    }

    public function getNearby(float $latitude, float $longitude, float $radiusKm = 5, int $perPage = 15): LengthAwarePaginator
    {
        // Using Haversine formula to calculate distance
        $query = Mosque::select('*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');

        return $query->paginate($perPage);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Mosque::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getByAdmin(int $userId): Collection
    {
        return Mosque::where('admin_user_id', $userId)->get();
    }
}
