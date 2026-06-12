<?php

namespace App\Contracts\Repositories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Collection;

interface FeeReportRepositoryInterface
{
    /**
     * Ringkasan fee: total_fee, count, average untuk periode bulan+tahun.
     * Hanya donasi dengan status 'confirmed'.
     *
     * @return array{total_fee: int, count: int, average: float}
     */
    public function getSummaryByPeriod(int $month, int $year): array;

    /**
     * Tren fee 12 bulan terakhir (untuk Chart.js).
     * Selalu mengembalikan tepat $months elemen, urutan ASC.
     * Bulan tanpa data memiliki total_fee = 0.
     *
     * @return array<int, array{label: string, total_fee: int}>
     */
    public function getMonthlyTrend(int $months = 12): array;

    /**
     * Baris detail donasi confirmed untuk export CSV.
     * Relasi mosque di-eager-load dengan kolom id dan name.
     *
     * @return Collection<int, Donation>
     */
    public function getConfirmedByPeriod(int $month, int $year): Collection;
}
