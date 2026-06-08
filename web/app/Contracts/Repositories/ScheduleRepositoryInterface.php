<?php

namespace App\Contracts\Repositories;

use App\Models\Schedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface
{
    /**
     * Get all schedules for a mosque with optional pagination.
     */
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a schedule by ID.
     */
    public function find(int $id): ?Schedule;

    /**
     * Get schedule by mosque and date.
     */
    public function findByDate(int $mosqueId, string $date): ?Schedule;

    /**
     * Create a new schedule.
     */
    public function create(array $data): Schedule;

    /**
     * Update an existing schedule.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a schedule.
     */
    public function delete(int $id): bool;

    /**
     * Get schedules for a date range.
     */
    public function getByDateRange(int $mosqueId, string $startDate, string $endDate): Collection;

    /**
     * Get today's schedule for a mosque.
     */
    public function getToday(int $mosqueId): ?Schedule;

    /**
     * Get upcoming schedules (from today onwards).
     */
    public function getUpcoming(int $mosqueId, int $days = 7): Collection;

    /**
     * Check if schedule exists for a mosque on a specific date.
     */
    public function existsForDate(int $mosqueId, string $date, ?int $excludeId = null): bool;

    /**
     * Bulk create or update schedules.
     */
    public function upsertMany(int $mosqueId, array $schedules): int;
}
