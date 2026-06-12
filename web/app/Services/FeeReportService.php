<?php

namespace App\Services;

use App\Contracts\Repositories\FeeReportRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeReportService
{
    public function __construct(private FeeReportRepositoryInterface $reportRepo) {}

    /**
     * Ringkasan statistik fee untuk periode terpilih.
     *
     * @return array{total_fee: int, count: int, average: float}
     */
    public function getSummary(int $month, int $year): array
    {
        return $this->reportRepo->getSummaryByPeriod($month, $year);
    }

    /**
     * Data chart 12 bulan terakhir (labels + values untuk Chart.js).
     *
     * @return array{labels: string[], values: int[]}
     */
    public function getChartData(): array
    {
        $trend = $this->reportRepo->getMonthlyTrend(12);

        return [
            'labels' => array_column($trend, 'label'),
            'values' => array_column($trend, 'total_fee'),
        ];
    }

    /**
     * Generate CSV sebagai StreamedResponse (tidak disimpan ke disk).
     * File CSV menggunakan BOM UTF-8 agar kompatibel dengan Excel.
     * Nama file: laporan-fee-{YYYY}-{MM}.csv
     */
    public function exportCsv(int $month, int $year): StreamedResponse
    {
        $filename  = sprintf('laporan-fee-%04d-%02d.csv', $year, $month);
        $donations = $this->reportRepo->getConfirmedByPeriod($month, $year);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($donations) {
            $handle = fopen('php://output', 'w');

            // BOM untuk Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Baris header
            fputcsv($handle, [
                'Tanggal Konfirmasi',
                'ID Donasi',
                'Nama Masjid',
                'Nominal Donasi',
                'Fee',
                'Mekanisme Fee',
            ]);

            // Baris data
            foreach ($donations as $donation) {
                fputcsv($handle, [
                    $donation->confirmed_at?->format('Y-m-d H:i:s') ?? '-',
                    $donation->id,
                    $donation->mosque?->name ?? '-',
                    $donation->amount,
                    $donation->fee_amount,
                    $donation->fee_mechanism,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
