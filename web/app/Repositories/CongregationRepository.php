<?php

namespace App\Repositories;

use App\Contracts\Repositories\CongregationRepositoryInterface;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CongregationRepository implements CongregationRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $mosque = Mosque::findOrFail($mosqueId);
        $query = $mosque->members();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $mosqueId, int $userId): ?User
    {
        $mosque = Mosque::findOrFail($mosqueId);

        return $mosque->members()->where('users.id', $userId)->first();
    }

    public function attach(int $mosqueId, int $userId, array $attributes = []): bool
    {
        $mosque = Mosque::findOrFail($mosqueId);

        if ($this->isMember($mosqueId, $userId)) {
            return false;
        }

        $defaultAttributes = ['joined_at' => now()];
        $mosque->members()->attach($userId, array_merge($defaultAttributes, $attributes));

        return true;
    }

    public function detach(int $mosqueId, int $userId): bool
    {
        $mosque = Mosque::findOrFail($mosqueId);
        $mosque->members()->detach($userId);

        return true;
    }

    public function isMember(int $mosqueId, int $userId): bool
    {
        $mosque = Mosque::findOrFail($mosqueId);

        return $mosque->members()->where('users.id', $userId)->exists();
    }

    public function getUserMosques(int $userId): Collection
    {
        $user = User::findOrFail($userId);

        return $user->mosques;
    }

    public function search(int $mosqueId, string $query, int $perPage = 15): LengthAwarePaginator
    {
        $mosque = Mosque::findOrFail($mosqueId);

        return $mosque->members()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('phone', 'like', '%' . $query . '%');
            })
            ->paginate($perPage);
    }

    public function count(int $mosqueId): int
    {
        $mosque = Mosque::findOrFail($mosqueId);

        return $mosque->members()->count();
    }

    public function getRecent(int $mosqueId, int $limit = 10): Collection
    {
        $mosque = Mosque::findOrFail($mosqueId);

        return $mosque->members()
            ->orderBy('mosque_user.joined_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
