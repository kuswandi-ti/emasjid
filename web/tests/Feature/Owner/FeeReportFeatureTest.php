<?php

namespace Tests\Feature\Owner;

use App\Models\Donation;
use App\Models\Mosque;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestMosque;

/**
 * Feature tests for the Fee Report page and CSV export.
 *
 * Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 7.2, 7.3, 7.4
 */
class FeeReportFeatureTest extends TestCase
{
    use CreatesTestMosque, RefreshDatabase;

    protected User $owner;
    protected User $nonOwner;
    protected Mosque $mosque;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->owner    = $this->createSuperAdmin();
        $this->nonOwner = $this->createUserWithNoRole();
        $this->mosque   = Mosque::factory()->create();
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Create N confirmed donations for a given month/year and mosque,
     * each with a specific fee_amount.
     */
    private function createConfirmedDonations(int $n, int $month, int $year, Mosque $mosque, int $feeAmount): void
    {
        for ($i = 0; $i < $n; $i++) {
            Donation::factory()->create([
                'status'       => 'confirmed',
                'confirmed_at' => now()->setYear($year)->setMonth($month)->setDay(15),
                'fee_amount'   => $feeAmount,
                'mosque_id'    => $mosque->id,
            ]);
        }
    }

    /**
     * Count non-empty lines in CSV content, normalising CRLF to LF first.
     */
    private function countCsvLines(string $content): int
    {
        // Strip BOM if present
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($content, $bom)) {
            $content = substr($content, strlen($bom));
        }

        // Normalise Windows line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        $lines = array_filter(explode("\n", trim($content)));

        return count($lines);
    }

    /**
     * Generate an array of unique (month, year) pairs in TIMESTAMP-safe range.
     * Uses years 2010–2037, months 1–12 → 336 unique slots available.
     */
    private function uniquePeriods(int $count, int $startYear = 2010): array
    {
        $periods = [];
        for ($y = $startYear; $y <= 2037; $y++) {
            for ($m = 1; $m <= 12; $m++) {
                $periods[] = [$m, $y];
                if (count($periods) >= $count) {
                    return $periods;
                }
            }
        }
        return $periods;
    }

    // ─── Property 6: Kalkulasi Ringkasan Laporan ─────────────────────────────

    /**
     * Property 6: For any N confirmed donations with a specific fee_amount in period M/Y,
     * the view's $summary['total_fee'] === N × feeAmount and $summary['count'] === N.
     *
     * Each iteration uses a unique (month, year) slot to guarantee isolation.
     *
     * Validates: Requirements 5.1, 5.3
     */
    public function test_property6_report_summary_calculation(): void
    {
        // 50 iterations — each uses a unique (month, year) pair from 2010 onward
        $periods = $this->uniquePeriods(50, 2010);

        foreach ($periods as [$month, $year]) {
            $n         = rand(1, 10);
            $feeAmount = rand(1_000, 50_000);

            $this->createConfirmedDonations($n, $month, $year, $this->mosque, $feeAmount);

            $expectedTotal = $n * $feeAmount;
            $expectedCount = $n;

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.index', ['month' => $month, 'year' => $year]));

            $response->assertOk();

            // Validate total_fee and count regardless of int/float casting
            $response->assertViewHas('summary', function ($summary) use ($expectedTotal, $expectedCount) {
                return (int) $summary['total_fee'] === $expectedTotal
                    && (int) $summary['count'] === $expectedCount;
            });
        }
    }

    // ─── Property 7: Filter Periode Laporan ──────────────────────────────────

    /**
     * Property 7: Donations outside the selected period must not be counted.
     * For any target period M/Y, confirmed donations in other months/years
     * must not affect total_fee or count.
     *
     * Iteration i uses targetYear=2010+2i and otherYear=2010+2i+1 so they never overlap.
     * All years stay within MySQL TIMESTAMP range (≤ 2037).
     *
     * Validates: Requirements 5.2, 5.3
     */
    public function test_property7_period_filter_excludes_other_months(): void
    {
        // 30 iterations — targetYear and otherYear advance 2 steps each iteration
        // Years used: 2010–2011, 2012–2013, ..., 2068–2069 → but capped at 2037
        // We only have (2037-2010)/2 = ~14 safe year-pairs; limit to 13 pairs
        // Use month variation to get 30 unique target periods across 13 year-pairs
        $iter = 0;
        for ($baseYear = 2010; $baseYear <= 2036 && $iter < 30; $baseYear += 2) {
            $targetYear = $baseYear;
            $otherYear  = $baseYear + 1;

            // Use months 1–12 for target, use a different month for other
            $targetMonth = ($iter % 12) + 1;

            // Create N donations IN the target period
            $nIn       = rand(1, 5);
            $feeIn     = rand(1_000, 10_000);
            $this->createConfirmedDonations($nIn, $targetMonth, $targetYear, $this->mosque, $feeIn);

            // Create M donations in the OTHER year (same or different month — guaranteed non-overlapping)
            $otherMonth = $targetMonth === 12 ? 1 : $targetMonth + 1;
            $nOut       = rand(1, 5);
            $feeOut     = rand(100_000, 500_000); // deliberately large to catch any leakage
            $this->createConfirmedDonations($nOut, $otherMonth, $otherYear, $this->mosque, $feeOut);

            $expectedTotal = $nIn * $feeIn;
            $expectedCount = $nIn;

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.index', ['month' => $targetMonth, 'year' => $targetYear]));

            $response->assertOk();
            $response->assertViewHas('summary', function ($summary) use ($expectedTotal, $expectedCount) {
                return (int) $summary['total_fee'] === $expectedTotal
                    && (int) $summary['count'] === $expectedCount;
            });

            $iter++;
        }

        // Ensure we reached the target iteration count
        $this->assertGreaterThanOrEqual(13, $iter, 'Expected at least 13 property-7 iterations');
    }

    // ─── Property 9 & 10: CSV content and filename ───────────────────────────

    /**
     * Property 9: CSV contains exactly N+1 lines (1 header + N data rows) for N donations.
     * Property 10: CSV filename matches format laporan-fee-{YYYY}-{MM}.csv.
     *
     * Uses uniquePeriods starting from 2030 to avoid overlap with Properties 6 and 7.
     * Property 6 uses 2010–2024 (50 months), Property 7 uses year pairs 2010–2036.
     * We keep Property 9/10 to 2025–2028 range (within TIMESTAMP-safe zone).
     *
     * Validates: Requirements 7.2, 7.3
     */
    public function test_property9_and_10_csv_row_count_and_filename(): void
    {
        // 30 iterations — use 2025–2029 range (safe TIMESTAMP range, 60 slots available)
        $periods = $this->uniquePeriods(30, 2025);

        foreach ($periods as $idx => [$month, $year]) {
            $n = rand(1, 8);

            $this->createConfirmedDonations($n, $month, $year, $this->mosque, rand(5_000, 50_000));

            $response = $this->actingAs($this->owner)
                ->get(route('owner.reports.fee.export', ['month' => $month, 'year' => $year]));

            $response->assertOk();

            // Property 10: verify filename in Content-Disposition
            $expectedFilename = sprintf('laporan-fee-%04d-%02d.csv', $year, $month);
            $response->assertHeader(
                'Content-Disposition',
                "attachment; filename=\"{$expectedFilename}\""
            );

            // Property 9: count lines (header + N data rows)
            $lineCount = $this->countCsvLines($response->streamedContent());

            $this->assertSame(
                $n + 1,
                $lineCount,
                "Iteration {$idx} ({$year}-{$month}): expected " . ($n + 1) . " lines (1 header + {$n} data rows), got {$lineCount}"
            );
        }
    }

    // ─── Edge Cases ──────────────────────────────────────────────────────────

    /**
     * Edge case: Empty period shows zero summary and the "no data" message.
     *
     * Validates: Requirement 5.4
     */
    public function test_empty_period_shows_zero_summary(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.reports.fee.index', ['month' => 6, 'year' => 2000]));

        $response->assertOk();
        $response->assertViewHas('summary', function ($summary) {
            return (int) $summary['total_fee'] === 0
                && (int) $summary['count'] === 0
                && (float) $summary['average'] === 0.0;
        });
        $response->assertSeeText('Belum ada data fee pada periode ini.');
    }

    /**
     * Edge case: CSV export with no data returns only the header row.
     *
     * Validates: Requirement 7.4
     */
    public function test_empty_period_csv_contains_only_header(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.reports.fee.export', ['month' => 6, 'year' => 2000]));

        $response->assertOk();

        $lineCount = $this->countCsvLines($response->streamedContent());

        $this->assertSame(1, $lineCount, 'CSV with no data should contain only the header row.');
    }

    /**
     * Edge case: Non-super-admin user gets HTTP 403 on report index.
     *
     * Validates: Requirement 5.5
     */
    public function test_non_super_admin_cannot_access_report_index(): void
    {
        $response = $this->actingAs($this->nonOwner)
            ->get(route('owner.reports.fee.index'));

        $response->assertForbidden();
    }

    /**
     * Edge case: Non-super-admin user gets HTTP 403 on CSV export.
     *
     * Validates: Requirement 7.4 (access restriction on export endpoint)
     */
    public function test_non_super_admin_cannot_access_csv_export(): void
    {
        $response = $this->actingAs($this->nonOwner)
            ->get(route('owner.reports.fee.export'));

        $response->assertForbidden();
    }
}
