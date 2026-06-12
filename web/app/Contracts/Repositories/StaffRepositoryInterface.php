<?php

namespace App\Contracts\Repositories;

use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    /**
     * Get all staff members for a mosque with optional filters and pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a staff member by ID.
     */
    public function find(int $id): ?Staff;

    /**
     * Get staff member by user ID for a specific mosque.
     */
    public function findByUser(int $mosqueId, int $userId): ?Staff;

    /**
     * Create a new staff member.
     */
    public function create(array $data): Staff;

    /**
     * Update an existing staff member.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a staff member.
     */
    public function delete(int $id): bool;

    /**
     * Get active staff members only.
     */
    public function getActive(int $mosqueId): Collection;

    /**
     * Get staff members by position.
     */
    public function getByPosition(int $mosqueId, string $position): Collection;

    /**
     * Activate a staff member.
     */
    public function activate(int $id): bool;

    /**
     * Deactivate a staff member.
     */
    public function deactivate(int $id): bool;

    /**
     * Check if a user is a staff member of a mosque.
     */
    public function isStaff(int $mosqueId, int $userId): bool;

    /**
     * Get all mosques where a user is a staff member.
     */
    public function getUserStaffMosques(int $userId): Collection;
}
