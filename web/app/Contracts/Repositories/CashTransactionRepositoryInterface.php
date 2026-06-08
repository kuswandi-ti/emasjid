<?php

namespace App\Contracts\Repositories;

use App\Models\CashTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CashTransactionRepositoryInterface
{
    /**
     * Get all cash transactions for a mosque with optional filters and pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a cash transaction by ID.
     */
    public function find(int $id): ?CashTransaction;

    /**
     * Create a new cash transaction.
     */
    public function create(array $data): CashTransaction;

    /**
     * Update an existing cash transaction.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a cash transaction.
     */
    public function delete(int $id): bool;

    /**
     * Get transactions by type (income/expense).
     */
    public function getByType(int $mosqueId, string $type, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get transactions by category.
     */
    public function getByCategory(int $mosqueId, string $category, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get transactions for a date range.
     */
    public function getByDateRange(int $mosqueId, string $startDate, string $endDate, ?string $type = null): Collection;

    /**
     * Calculate total balance for a mosque.
     */
    public function getBalance(int $mosqueId, ?string $endDate = null): int;

    /**
     * Get summary by type (total income and expense).
     */
    public function getSummary(int $mosqueId, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Get transactions recorded by a specific user.
     */
    public function getByRecorder(int $mosqueId, int $userId, int $perPage = 15): LengthAwarePaginator;
}
