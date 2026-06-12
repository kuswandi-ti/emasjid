<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for managing congregation members (jamaah) of a mosque.
 * Uses the mosque_user pivot table.
 */
interface CongregationRepositoryInterface
{
    /**
     * Get all congregation members for a mosque with optional filters and pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a congregation member by user ID for a specific mosque.
     */
    public function find(int $mosqueId, int $userId): ?User;

    /**
     * Add a user as a congregation member to a mosque.
     */
    public function attach(int $mosqueId, int $userId, array $attributes = []): bool;

    /**
     * Remove a user from mosque congregation.
     */
    public function detach(int $mosqueId, int $userId): bool;

    /**
     * Check if a user is a member of a mosque.
     */
    public function isMember(int $mosqueId, int $userId): bool;

    /**
     * Get all mosques a user has joined.
     */
    public function getUserMosques(int $userId): Collection;

    /**
     * Search congregation members by name or email.
     */
    public function search(int $mosqueId, string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Count total congregation members for a mosque.
     */
    public function count(int $mosqueId): int;

    /**
     * Get recent members who joined.
     */
    public function getRecent(int $mosqueId, int $limit = 10): Collection;
}
