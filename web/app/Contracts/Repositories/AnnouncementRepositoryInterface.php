<?php

namespace App\Contracts\Repositories;

use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AnnouncementRepositoryInterface
{
    /**
     * Get all announcements for a mosque with optional filters and pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get an announcement by ID.
     */
    public function find(int $id): ?Announcement;

    /**
     * Create a new announcement.
     */
    public function create(array $data): Announcement;

    /**
     * Update an existing announcement.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete an announcement.
     */
    public function delete(int $id): bool;

    /**
     * Get announcements by status.
     */
    public function getByStatus(int $mosqueId, string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get published announcements only.
     */
    public function getPublished(int $mosqueId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Publish an announcement.
     */
    public function publish(int $id, int $publishedBy): bool;

    /**
     * Archive an announcement.
     */
    public function archive(int $id): bool;

    /**
     * Get recent published announcements.
     */
    public function getRecent(int $mosqueId, int $limit = 5): Collection;

    /**
     * Search announcements by title or content.
     */
    public function search(int $mosqueId, string $query, int $perPage = 15): LengthAwarePaginator;
}
