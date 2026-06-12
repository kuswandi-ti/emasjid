<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\FeeReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private FeeReportService $reportService) {}

    /**
     * Tampilkan halaman laporan fee bulanan.
     * Membaca `month` dan `year` dari query string,
     * dengan default bulan dan tahun saat ini.
     */
    public function feeIndex(Request $request): View
    {
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear  = (int) $request->input('year', now()->year);

        $summary   = $this->reportService->getSummary($selectedMonth, $selectedYear);
        $chartData = $this->reportService->getChartData();

        return view('owner.reports.fee', compact(
            'summary', 'chartData', 'selectedMonth', 'selectedYear'
        ));
    }

    /**
     * Export data laporan fee sebagai file CSV.
     * Membaca `month` dan `year` dari query string,
     * dengan default bulan dan tahun saat ini.
     */
    public function feeExport(Request $request): StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        return $this->reportService->exportCsv($month, $year);
    }
}
