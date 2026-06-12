<?php

namespace App\Contracts\Repositories;

use App\Models\Donation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DonationRepositoryInterface
{
    /**
     * Get all donations for a mosque with optional filters and pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a donation by ID.
     */
    public function find(int $id): ?Donation;

    /**
     * Get donation by merchant order ID.
     */
    public function findByMerchantOrderId(string $merchantOrderId): ?Donation;

    /**
     * Create a new donation.
     */
    public function create(array $data): Donation;

    /**
     * Update an existing donation.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a donation (should be restricted).
     */
    public function delete(int $id): bool;

    /**
     * Get donations by status.
     */
    public function getByStatus(int $mosqueId, string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get donations by category.
     */
    public function getByCategory(int $mosqueId, string $category, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get donations by user (donor).
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get donations for a date range.
     */
    public function getByDateRange(int $mosqueId, string $startDate, string $endDate, ?string $status = null): Collection;

    /**
     * Calculate total donations for a mosque.
     */
    public function getTotalDonations(int $mosqueId, ?string $status = 'confirmed', ?string $startDate = null, ?string $endDate = null): int;

    /**
     * Get donation summary by category.
     */
    public function getSummaryByCategory(int $mosqueId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Get recent donations.
     */
    public function getRecent(int $mosqueId, int $limit = 10): Collection;

    /**
     * Mark donation as confirmed.
     */
    public function markAsConfirmed(int $id, array $data = []): bool;

    /**
     * Mark donation as failed.
     */
    public function markAsFailed(int $id, ?string $reason = null): bool;

    /**
     * Mark donation as expired.
     */
    public function markAsExpired(int $id): bool;
}
