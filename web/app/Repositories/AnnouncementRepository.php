<?php

namespace App\Repositories;

use App\Contracts\Repositories\AnnouncementRepositoryInterface;
use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Announcement::where('mosque_id', $mosqueId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['mosque', 'publisher'])->latest('published_at')->paginate($perPage);
    }

    public function find(int $id): ?Announcement
    {
        return Announcement::with(['mosque', 'publisher'])->find($id);
    }

    public function create(array $data): Announcement
    {
        return Announcement::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Announcement::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Announcement::where('id', $id)->delete();
    }

    public function getByStatus(int $mosqueId, string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::where('mosque_id', $mosqueId)
            ->where('status', $status)
            ->with(['mosque', 'publisher'])
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function getPublished(int $mosqueId, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::where('mosque_id', $mosqueId)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->with(['mosque', 'publisher'])
            ->latest('published_at')
            ->paginate($perPage);
    }

    public function publish(int $id, int $publishedBy): bool
    {
        return Announcement::where('id', $id)->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $publishedBy,
        ]);
    }

    public function archive(int $id): bool
    {
        return Announcement::where('id', $id)->update([
            'status' => 'archived',
        ]);
    }

    public function getRecent(int $mosqueId, int $limit = 5): Collection
    {
        return Announcement::where('mosque_id', $mosqueId)
            ->where('status', 'published')
            ->with('publisher')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function search(int $mosqueId, string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::where('mosque_id', $mosqueId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('content', 'like', '%' . $query . '%');
            })
            ->with(['mosque', 'publisher'])
            ->latest('published_at')
            ->paginate($perPage);
    }
}
