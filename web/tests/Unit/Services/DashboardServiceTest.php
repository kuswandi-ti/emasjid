<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Models\Donation;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for DashboardService.
 *
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 8.2
 */
class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private MosqueRepositoryInterface $mosqueRepository;
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of MosqueRepositoryInterface
        $this->mosqueRepository = Mockery::mock(MosqueRepositoryInterface::class);

        // Instantiate the service with the mocked repository
        $this->service = new DashboardService($this->mosqueRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Set up default mock expectations for the mosque repository.
     */
    private function mockRepositoryDefaults(int $active = 0, int $pending = 0, int $suspended = 0, int $congregation = 0): void
    {
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Active)
            ->andReturn($active);

        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Pending)
            ->andReturn($pending);

        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Suspended)
            ->andReturn($suspended);

        $this->mosqueRepository
            ->shouldReceive('getTotalCongregationCount')
            ->andReturn($congregation);
    }

    // -------------------------------------------------------------------------
    // getStatistics() — structure and correctness
    // -------------------------------------------------------------------------

    /**
     * Test getStatistics returns array with all required keys.
     *
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
     */
    public function test_get_statistics_returns_array_with_all_required_keys(): void
    {
        $this->mockRepositoryDefaults(active: 10, pending: 3, suspended: 1, congregation: 250);

        $statistics = $this->service->getStatistics();

        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('total_mosques_active', $statistics);
        $this->assertArrayHasKey('total_mosques_pending', $statistics);
        $this->assertArrayHasKey('total_mosques_suspended', $statistics);
        $this->assertArrayHasKey('total_congregation', $statistics);
        $this->assertArrayHasKey('total_platform_fees', $statistics);
        $this->assertArrayHasKey('total_platform_fees_formatted', $statistics);
    }

    /**
     * Test getStatistics returns correct values from repository.
     *
     * Requirements: 2.1, 2.2, 2.3, 2.4
     */
    public function test_get_statistics_returns_correct_values_from_repository(): void
    {
        $this->mockRepositoryDefaults(active: 15, pending: 5, suspended: 2, congregation: 500);

        $statistics = $this->service->getStatistics();

        $this->assertEquals(15, $statistics['total_mosques_active']);
        $this->assertEquals(5, $statistics['total_mosques_pending']);
        $this->assertEquals(2, $statistics['total_mosques_suspended']);
        $this->assertEquals(500, $statistics['total_congregation']);
    }

    /**
     * Test getStatistics total_platform_fees is an integer.
     *
     * Requirements: 2.3, 2.5
     */
    public function test_get_statistics_total_platform_fees_is_integer(): void
    {
        $this->mockRepositoryDefaults();

        $statistics = $this->service->getStatistics();

        $this->assertIsInt($statistics['total_platform_fees']);
    }

    /**
     * Test getStatistics total_platform_fees_formatted is a string with Rupiah format.
     *
     * Requirements: 2.4, 2.6
     */
    public function test_get_statistics_total_platform_fees_formatted_is_rupiah_string(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults();

        // Create a confirmed donation so fees are > 0
        Donation::factory()->confirmed()->create(['fee_amount' => 2500]);

        $statistics = $this->service->getStatistics();

        $this->assertIsString($statistics['total_platform_fees_formatted']);
        $this->assertStringStartsWith('Rp ', $statistics['total_platform_fees_formatted']);
    }

    // -------------------------------------------------------------------------
    // Caching behaviour
    // -------------------------------------------------------------------------

    /**
     * Test getStatistics result is cached and repository is called only once.
     *
     * Requirements: 8.2
     */
    public function test_get_statistics_caches_result(): void
    {
        Cache::flush();

        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Active)
            ->once()
            ->andReturn(7);

        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Pending)
            ->once()
            ->andReturn(2);

        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Suspended)
            ->once()
            ->andReturn(0);

        $this->mosqueRepository
            ->shouldReceive('getTotalCongregationCount')
            ->once()
            ->andReturn(100);

        // First call — should hit repository
        $first = $this->service->getStatistics();

        // Second call — should return cached result without calling repository again
        $second = $this->service->getStatistics();

        $this->assertEquals($first, $second);
    }

    /**
     * Test the cache key 'owner.dashboard.statistics' is used.
     *
     * Requirements: 8.2
     */
    public function test_get_statistics_stores_under_correct_cache_key(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults(active: 3, congregation: 50);

        $this->service->getStatistics();

        $this->assertTrue(Cache::has('owner.dashboard.statistics'));
    }

    // -------------------------------------------------------------------------
    // getPlatformFees() — tested via getStatistics()
    // -------------------------------------------------------------------------

    /**
     * Test getPlatformFees sums only confirmed donation fees.
     *
     * Requirements: 2.3, 2.5
     */
    public function test_get_platform_fees_sums_confirmed_donation_fees_only(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults();

        // One confirmed donation with a known fee
        Donation::factory()->confirmed()->create(['fee_amount' => 2500]);

        // A pending donation — should NOT be counted
        Donation::factory()->create(['fee_amount' => 1250]); // default status = pending

        // A failed donation — should NOT be counted
        Donation::factory()->failed()->create(['fee_amount' => 1875]);

        $statistics = $this->service->getStatistics();

        // Only the confirmed donation's fee_amount (2500) should be included
        $this->assertEquals(2500, $statistics['total_platform_fees']);
    }

    /**
     * Test getPlatformFees returns 0 when no confirmed donations exist.
     *
     * Requirements: 2.3, 2.5
     */
    public function test_get_platform_fees_returns_zero_when_no_confirmed_donations(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults();

        $statistics = $this->service->getStatistics();

        $this->assertEquals(0, $statistics['total_platform_fees']);
    }

    /**
     * Test getPlatformFees sums fees from multiple confirmed donations.
     *
     * Requirements: 2.3, 2.5
     */
    public function test_get_platform_fees_sums_multiple_confirmed_donations(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults();

        Donation::factory()->confirmed()->create(['fee_amount' => 2500]);
        Donation::factory()->confirmed()->create(['fee_amount' => 5000]);
        Donation::factory()->confirmed()->create(['fee_amount' => 1250]);

        $statistics = $this->service->getStatistics();

        // 2500 + 5000 + 1250 = 8750
        $this->assertEquals(8750, $statistics['total_platform_fees']);
    }

    /**
     * Test getPlatformFees formatted value matches the raw integer amount.
     *
     * Requirements: 2.4, 2.6
     */
    public function test_get_platform_fees_formatted_matches_raw_fee_amount(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults();

        Donation::factory()->confirmed()->create(['fee_amount' => 1250000]);

        $statistics = $this->service->getStatistics();

        $this->assertEquals(1250000, $statistics['total_platform_fees']);
        $this->assertEquals('Rp 1.250.000', $statistics['total_platform_fees_formatted']);
    }

    // -------------------------------------------------------------------------
    // clearStatisticsCache()
    // -------------------------------------------------------------------------

    /**
     * Test clearStatisticsCache removes cached statistics.
     *
     * Requirements: 8.2
     */
    public function test_clear_statistics_cache_removes_cached_data(): void
    {
        Cache::flush();
        $this->mockRepositoryDefaults(active: 5, congregation: 100);

        // Populate the cache
        $this->service->getStatistics();
        $this->assertTrue(Cache::has('owner.dashboard.statistics'));

        // Clear it
        $this->service->clearStatisticsCache();

        $this->assertFalse(Cache::has('owner.dashboard.statistics'));
    }

    /**
     * Test clearStatisticsCache forces fresh data on next getStatistics call.
     *
     * Requirements: 8.2
     */
    public function test_clear_statistics_cache_forces_fresh_data_on_next_call(): void
    {
        Cache::flush();

        // First round: active = 5
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Active)
            ->once()
            ->andReturn(5);
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Pending)
            ->once()
            ->andReturn(0);
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Suspended)
            ->once()
            ->andReturn(0);
        $this->mosqueRepository
            ->shouldReceive('getTotalCongregationCount')
            ->once()
            ->andReturn(50);

        $first = $this->service->getStatistics();
        $this->assertEquals(5, $first['total_mosques_active']);

        // Clear cache so next call re-queries
        $this->service->clearStatisticsCache();

        // Second round: active = 20
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Active)
            ->once()
            ->andReturn(20);
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Pending)
            ->once()
            ->andReturn(1);
        $this->mosqueRepository
            ->shouldReceive('countByStatus')
            ->with(MosqueStatus::Suspended)
            ->once()
            ->andReturn(0);
        $this->mosqueRepository
            ->shouldReceive('getTotalCongregationCount')
            ->once()
            ->andReturn(200);

        $second = $this->service->getStatistics();
        $this->assertEquals(20, $second['total_mosques_active']);
    }
}
