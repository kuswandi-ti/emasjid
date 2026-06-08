<?php

namespace App\Contracts\Repositories;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MosqueRepositoryInterface
{
    /**
     * Get all mosques with optional filters and pagination.
     */
    public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a mosque by ID.
     */
    public function find(int $id): ?Mosque;

    /**
     * Get a mosque by slug.
     */
    public function findBySlug(string $slug): ?Mosque;

    /**
     * Get a mosque by invitation code.
     */
    public function findByInvitationCode(string $code): ?Mosque;

    /**
     * Create a new mosque.
     */
    public function create(array $data): Mosque;

    /**
     * Update an existing mosque.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a mosque.
     */
    public function delete(int $id): bool;

    /**
     * Get mosques by status.
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get mosques by city.
     */
    public function getByCity(string $city, int $perPage = 15): LengthAwarePaginator;

    /**
     * Search mosques by name or location.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get nearby mosques by coordinates.
     */
    public function getNearby(float $latitude, float $longitude, float $radiusKm = 5, int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if slug exists.
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * Get mosques administered by a user.
     */
    public function getByAdmin(int $userId): Collection;

    /**
     * Count mosques by status.
     */
    public function countByStatus(MosqueStatus $status): int;

    /**
     * Get total congregation count across all active mosques.
     */
    public function getTotalCongregationCount(): int;

    /**
     * Get pending mosques with search support.
     */
    public function getPending(?string $search, int $perPage): LengthAwarePaginator;

    /**
     * Find mosque with eager-loaded relationships.
     */
    public function findWithRelations(int $id, array $relations): ?Mosque;
}
