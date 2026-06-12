<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\FeeReportRepositoryInterface;
use App\Services\FeeReportService;
use Tests\TestCase;

/**
 * Unit property tests for FeeReportService.
 *
 * **Validates: Requirements 5.1, 5.3, 6.1, 6.3**
 */
class FeeReportServiceTest extends TestCase
{
    /**
     * Property 6: Kalkulasi Ringkasan Laporan
     *
     * For any count and total_fee returned by the repository, the service must
     * pass through total_fee and count unchanged, and average must equal
     * total_fee / count (or 0 when count = 0).
     *
     * **Validates: Requirements 5.1, 5.3**
     */
    public function test_property6_summary_average_calculation(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $count    = rand(0, 50);
            $totalFee = $count > 0 ? rand(1000, 1000000) : 0;
            $expected = $count > 0 ? $totalFee / $count : 0.0;

            $repo = $this->createMock(FeeReportRepositoryInterface::class);
            $repo->method('getSummaryByPeriod')->willReturn([
                'total_fee' => $totalFee,
                'count'     => $count,
                'average'   => $expected,
            ]);

            $service = new FeeReportService($repo);
            $summary = $service->getSummary(rand(1, 12), rand(2020, 2026));

            $this->assertSame(
                $totalFee,
                $summary['total_fee'],
                "Iteration {$i}: total_fee mismatch — expected {$totalFee}, got {$summary['total_fee']}"
            );
            $this->assertSame(
                $count,
                $summary['count'],
                "Iteration {$i}: count mismatch — expected {$count}, got {$summary['count']}"
            );
            $this->assertEquals(
                $expected,
                $summary['average'],
                "Iteration {$i}: average mismatch — expected {$expected}, got {$summary['average']}"
            );
        }
    }

    /**
     * Property 6 edge case: count = 0 → average must be exactly 0.0
     *
     * **Validates: Requirements 5.1, 5.3**
     */
    public function test_property6_summary_average_is_zero_when_count_is_zero(): void
    {
        $repo = $this->createMock(FeeReportRepositoryInterface::class);
        $repo->method('getSummaryByPeriod')->willReturn([
            'total_fee' => 0,
            'count'     => 0,
            'average'   => 0.0,
        ]);

        $service = new FeeReportService($repo);
        $summary = $service->getSummary(1, 2024);

        $this->assertSame(0, $summary['total_fee']);
        $this->assertSame(0, $summary['count']);
        $this->assertEquals(0.0, $summary['average']);
    }

    /**
     * Property 8: Chart Selalu 12 Data Point
     *
     * For any 12-element trend returned by the repository, getChartData() must
     * always return exactly 12 labels and exactly 12 values.
     *
     * **Validates: Requirements 6.1, 6.3**
     */
    public function test_property8_chart_always_has_12_data_points(): void
    {
        for ($i = 0; $i < 100; $i++) {
            // Build random 12-element trend
            $trend = [];
            for ($j = 0; $j < 12; $j++) {
                $year    = 2020 + rand(0, 5);
                $month   = $j + 1;
                $trend[] = [
                    'label'     => date('M Y', mktime(0, 0, 0, $month, 1, $year)),
                    'month'     => $month,
                    'year'      => $year,
                    'total_fee' => rand(0, 100000),
                ];
            }

            $repo = $this->createMock(FeeReportRepositoryInterface::class);
            $repo->method('getMonthlyTrend')->willReturn($trend);

            $service   = new FeeReportService($repo);
            $chartData = $service->getChartData();

            $this->assertArrayHasKey('labels', $chartData,
                "Iteration {$i}: chartData must have 'labels' key");
            $this->assertArrayHasKey('values', $chartData,
                "Iteration {$i}: chartData must have 'values' key");
            $this->assertCount(12, $chartData['labels'],
                "Iteration {$i}: labels must have exactly 12 elements");
            $this->assertCount(12, $chartData['values'],
                "Iteration {$i}: values must have exactly 12 elements");
        }
    }

    /**
     * Property 8 structural check: labels and values must be flat arrays
     * whose elements correspond positionally to the trend data.
     *
     * **Validates: Requirements 6.1, 6.3**
     */
    public function test_property8_chart_labels_and_values_match_trend_order(): void
    {
        $trend = [];
        for ($j = 0; $j < 12; $j++) {
            $trend[] = [
                'label'     => 'Month ' . ($j + 1),
                'month'     => $j + 1,
                'year'      => 2024,
                'total_fee' => ($j + 1) * 1000,
            ];
        }

        $repo = $this->createMock(FeeReportRepositoryInterface::class);
        $repo->method('getMonthlyTrend')->willReturn($trend);

        $service   = new FeeReportService($repo);
        $chartData = $service->getChartData();

        foreach ($trend as $index => $slot) {
            $this->assertSame(
                $slot['label'],
                $chartData['labels'][$index],
                "labels[{$index}] must match trend label '{$slot['label']}'"
            );
            $this->assertSame(
                $slot['total_fee'],
                $chartData['values'][$index],
                "values[{$index}] must match trend total_fee {$slot['total_fee']}"
            );
        }
    }
}
