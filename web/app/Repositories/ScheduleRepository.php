<?php

namespace App\Repositories;

use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Models\Schedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Schedule::where('mosque_id', $mosqueId);

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Schedule
    {
        return Schedule::find($id);
    }

    public function findByDate(int $mosqueId, string $date): ?Schedule
    {
        return Schedule::where('mosque_id', $mosqueId)
            ->where('date', $date)
            ->first();
    }

    public function create(array $data): Schedule
    {
        return Schedule::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Schedule::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Schedule::where('id', $id)->delete();
    }

    public function getByDateRange(int $mosqueId, string $startDate, string $endDate): Collection
    {
        return Schedule::where('mosque_id', $mosqueId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    public function getToday(int $mosqueId): ?Schedule
    {
        return Schedule::where('mosque_id', $mosqueId)
            ->where('date', now()->toDateString())
            ->first();
    }

    public function getUpcoming(int $mosqueId, int $days = 7): Collection
    {
        return Schedule::where('mosque_id', $mosqueId)
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('date')
            ->get();
    }

    public function existsForDate(int $mosqueId, string $date, ?int $excludeId = null): bool
    {
        $query = Schedule::where('mosque_id', $mosqueId)
            ->where('date', $date);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function upsertMany(int $mosqueId, array $schedules): int
    {
        $count = 0;

        foreach ($schedules as $scheduleData) {
            $scheduleData['mosque_id'] = $mosqueId;

            Schedule::updateOrCreate(
                [
                    'mosque_id' => $mosqueId,
                    'date' => $scheduleData['date'],
                ],
                $scheduleData
            );

            $count++;
        }

        return $count;
    }
}
