<?php

namespace App\Repositories;

use App\Contracts\Repositories\FeeReportRepositoryInterface;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Collection;

class FeeReportRepository implements FeeReportRepositoryInterface
{
    /**
     * Ringkasan fee: total_fee, count, average untuk periode bulan+tahun.
     * Hanya donasi dengan status 'confirmed'.
     *
     * @return array{total_fee: int, count: int, average: float}
     */
    public function getSummaryByPeriod(int $month, int $year): array
    {
        $result = Donation::confirmed()
            ->inPeriod($month, $year)
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as total_fee, COUNT(*) as count')
            ->first();

        $count    = (int) $result->count;
        $totalFee = (int) $result->total_fee;
        $average  = $count > 0 ? $totalFee / $count : 0.0;

        return [
            'total_fee' => $totalFee,
            'count'     => $count,
            'average'   => $average,
        ];
    }

    /**
     * Tren fee 12 bulan terakhir (untuk Chart.js).
     * Selalu mengembalikan tepat $months elemen, urutan ASC.
     * Bulan tanpa data memiliki total_fee = 0.
     *
     * @return array<int, array{label: string, total_fee: int}>
     */
    public function getMonthlyTrend(int $months = 12): array
    {
        // Bangun grid penuh $months bulan terakhir di PHP
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date     = now()->subMonths($i)->startOfMonth();
            $result[] = [
                'label'     => $date->format('M Y'),
                'month'     => (int) $date->month,
                'year'      => (int) $date->year,
                'total_fee' => 0,
            ];
        }

        // Query satu kali untuk semua bulan dalam rentang
        $start = now()->subMonths($months - 1)->startOfMonth();
        $rows  = Donation::confirmed()
            ->where('confirmed_at', '>=', $start)
            ->selectRaw('MONTH(confirmed_at) as month, YEAR(confirmed_at) as year, COALESCE(SUM(fee_amount), 0) as total_fee')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($r) => "{$r->year}-{$r->month}");

        // Gabungkan — bulan tanpa data tetap 0
        foreach ($result as &$slot) {
            $key = "{$slot['year']}-{$slot['month']}";
            if ($rows->has($key)) {
                $slot['total_fee'] = (int) $rows[$key]->total_fee;
            }
        }

        return $result;
    }

    /**
     * Baris detail donasi confirmed untuk export CSV.
     * Relasi mosque di-eager-load dengan kolom id dan name.
     *
     * @return Collection<int, Donation>
     */
    public function getConfirmedByPeriod(int $month, int $year): Collection
    {
        return Donation::confirmed()
            ->inPeriod($month, $year)
            ->with('mosque:id,name')
            ->orderBy('confirmed_at')
            ->get();
    }
}
