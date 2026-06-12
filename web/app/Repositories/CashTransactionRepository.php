<?php

namespace App\Repositories;

use App\Contracts\Repositories\CashTransactionRepositoryInterface;
use App\Models\CashTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CashTransactionRepository implements CashTransactionRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CashTransaction::where('mosque_id', $mosqueId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('notes', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with('recorder')->latest('transaction_date')->paginate($perPage);
    }

    public function find(int $id): ?CashTransaction
    {
        return CashTransaction::with('recorder')->find($id);
    }

    public function create(array $data): CashTransaction
    {
        return CashTransaction::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return CashTransaction::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return CashTransaction::where('id', $id)->delete();
    }

    public function getByType(int $mosqueId, string $type, int $perPage = 15): LengthAwarePaginator
    {
        return CashTransaction::where('mosque_id', $mosqueId)
            ->where('type', $type)
            ->with('recorder')
            ->latest('transaction_date')
            ->paginate($perPage);
    }

    public function getByCategory(int $mosqueId, string $category, int $perPage = 15): LengthAwarePaginator
    {
        return CashTransaction::where('mosque_id', $mosqueId)
            ->where('category', $category)
            ->with('recorder')
            ->latest('transaction_date')
            ->paginate($perPage);
    }

    public function getByDateRange(int $mosqueId, string $startDate, string $endDate, ?string $type = null): Collection
    {
        $query = CashTransaction::where('mosque_id', $mosqueId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('transaction_date')->get();
    }

    public function getBalance(int $mosqueId, ?string $endDate = null): int
    {
        $query = CashTransaction::where('mosque_id', $mosqueId);

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return $income - $expense;
    }

    public function getSummary(int $mosqueId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = CashTransaction::where('mosque_id', $mosqueId);

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getByRecorder(int $mosqueId, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return CashTransaction::where('mosque_id', $mosqueId)
            ->where('recorded_by', $userId)
            ->with('recorder')
            ->latest('transaction_date')
            ->paginate($perPage);
    }
}
