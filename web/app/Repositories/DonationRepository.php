<?php

namespace App\Repositories;

use App\Contracts\Repositories\DonationRepositoryInterface;
use App\Models\Donation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DonationRepository implements DonationRepositoryInterface
{
    public function all(int $mosqueId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Donation::where('mosque_id', $mosqueId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['is_anonymous'])) {
            $query->where('is_anonymous', $filters['is_anonymous']);
        }

        return $query->with(['donor', 'mosque'])->latest()->paginate($perPage);
    }

    public function find(int $id): ?Donation
    {
        return Donation::with(['donor', 'mosque'])->find($id);
    }

    public function findByMerchantOrderId(string $merchantOrderId): ?Donation
    {
        return Donation::where('merchant_order_id', $merchantOrderId)->first();
    }

    public function create(array $data): Donation
    {
        return Donation::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Donation::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        // Note: Donations should rarely be deleted for audit trail purposes
        return Donation::where('id', $id)->delete();
    }

    public function getByStatus(int $mosqueId, string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Donation::where('mosque_id', $mosqueId)
            ->where('status', $status)
            ->with(['donor', 'mosque'])
            ->latest()
            ->paginate($perPage);
    }

    public function getByCategory(int $mosqueId, string $category, int $perPage = 15): LengthAwarePaginator
    {
        return Donation::where('mosque_id', $mosqueId)
            ->where('category', $category)
            ->with(['donor', 'mosque'])
            ->latest()
            ->paginate($perPage);
    }

    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Donation::where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['mosque_id'])) {
            $query->where('mosque_id', $filters['mosque_id']);
        }

        return $query->with('mosque')->latest()->paginate($perPage);
    }

    public function getByDateRange(int $mosqueId, string $startDate, string $endDate, ?string $status = null): Collection
    {
        $query = Donation::where('mosque_id', $mosqueId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with('donor')->orderBy('created_at')->get();
    }

    public function getTotalDonations(int $mosqueId, ?string $status = 'confirmed', ?string $startDate = null, ?string $endDate = null): int
    {
        $query = Donation::where('mosque_id', $mosqueId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->sum('mosque_receives');
    }

    public function getSummaryByCategory(int $mosqueId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Donation::where('mosque_id', $mosqueId)
            ->where('status', 'confirmed');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->selectRaw('category, SUM(mosque_receives) as total, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->keyBy('category')
            ->map(fn ($item) => [
                'total' => $item->total,
                'count' => $item->count,
            ])
            ->toArray();
    }

    public function getRecent(int $mosqueId, int $limit = 10): Collection
    {
        return Donation::where('mosque_id', $mosqueId)
            ->where('status', 'confirmed')
            ->with('donor')
            ->latest('confirmed_at')
            ->limit($limit)
            ->get();
    }

    public function markAsConfirmed(int $id, array $data = []): bool
    {
        return Donation::where('id', $id)->update(array_merge([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ], $data));
    }

    public function markAsFailed(int $id, ?string $reason = null): bool
    {
        $data = ['status' => 'failed'];

        if ($reason) {
            $data['notes'] = $reason;
        }

        return Donation::where('id', $id)->update($data);
    }

    public function markAsExpired(int $id): bool
    {
        return Donation::where('id', $id)->update([
            'status' => 'expired',
        ]);
    }
}
