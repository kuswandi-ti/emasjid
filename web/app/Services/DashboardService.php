<?php

namespace App\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\DonationStatus;
use App\Enums\MosqueStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected MosqueRepositoryInterface $mosqueRepository
    ) {}

    /**
     * Get platform statistics with caching.
     *
     * @return array{
     *   total_mosques_active: int,
     *   total_mosques_pending: int,
     *   total_mosques_suspended: int,
     *   total_congregation: int,
     *   total_platform_fees: int,
     *   total_platform_fees_formatted: string
     * }
     */
    public function getStatistics(): array
    {
        return Cache::remember('owner.dashboard.statistics', now()->addMinutes(5), function () {
            $platformFees = $this->getPlatformFees();

            return [
                'total_mosques_active' => $this->mosqueRepository
                    ->countByStatus(MosqueStatus::Active),
                'total_mosques_pending' => $this->mosqueRepository
                    ->countByStatus(MosqueStatus::Pending),
                'total_mosques_suspended' => $this->mosqueRepository
                    ->countByStatus(MosqueStatus::Suspended),
                'total_congregation' => $this->mosqueRepository
                    ->getTotalCongregationCount(),
                'total_platform_fees' => $platformFees,
                'total_platform_fees_formatted' => format_rupiah($platformFees),
            ];
        });
    }

    /**
     * Calculate total platform fees from confirmed donations.
     */
    protected function getPlatformFees(): int
    {
        return (int) DB::table('donations')
            ->where('status', DonationStatus::Confirmed->value)
            ->sum('fee_amount');
    }

    /**
     * Clear cached statistics.
     */
    public function clearStatisticsCache(): void
    {
        Cache::forget('owner.dashboard.statistics');
    }
}
