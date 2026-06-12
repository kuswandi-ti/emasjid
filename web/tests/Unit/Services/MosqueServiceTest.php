<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for MosqueService.
 *
 * Requirements: 3.1, 4.1, 5.1, 4.4
 */
class MosqueServiceTest extends TestCase
{
    private MosqueRepositoryInterface $mosqueRepository;
    private MosqueService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mosqueRepository = Mockery::mock(MosqueRepositoryInterface::class);
        $this->service = new MosqueService($this->mosqueRepository);
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
     * Create an empty paginator for testing delegation.
     */
    private function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 15);
    }

    /**
     * Create a mock Mosque model with a given status and created_at date.
     */
    private function makeMosque(MosqueStatus $status, ?Carbon $createdAt = null): Mosque
    {
        $mosque = Mockery::mock(Mosque::class)->makePartial();
        $mosque->status = $status;
        $mosque->created_at = $createdAt ?? now();

        return $mosque;
    }

    // -------------------------------------------------------------------------
    // getMosqueList() — Requirements: 3.1
    // -------------------------------------------------------------------------

    /**
     * Test getMosqueList delegates to repository's all() method with correct arguments.
     *
     * Requirements: 3.1
     */
    public function test_get_mosque_list_delegates_to_repository_all_method(): void
    {
        $filters = ['status' => 'active', 'search' => 'masjid'];
        $perPage = 20;
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('all')
            ->once()
            ->with($filters, $perPage)
            ->andReturn($paginator);

        $result = $this->service->getMosqueList($filters, $perPage);

        $this->assertSame($paginator, $result);
    }

    /**
     * Test getMosqueList uses default perPage value of 15 when not specified.
     *
     * Requirements: 3.1
     */
    public function test_get_mosque_list_uses_default_per_page_of_15(): void
    {
        $filters = [];
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('all')
            ->once()
            ->with($filters, 15)
            ->andReturn($paginator);

        $result = $this->service->getMosqueList($filters);

        $this->assertSame($paginator, $result);
    }

    /**
     * Test getMosqueList passes empty filters array to repository.
     *
     * Requirements: 3.1
     */
    public function test_get_mosque_list_passes_empty_filters_to_repository(): void
    {
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('all')
            ->once()
            ->with([], 15)
            ->andReturn($paginator);

        $result = $this->service->getMosqueList([]);

        $this->assertSame($paginator, $result);
    }

    // -------------------------------------------------------------------------
    // getPendingMosques() — Requirements: 4.1
    // -------------------------------------------------------------------------

    /**
     * Test getPendingMosques delegates to repository's getPending() method.
     *
     * Requirements: 4.1
     */
    public function test_get_pending_mosques_delegates_to_repository_get_pending_method(): void
    {
        $search = 'al-ikhlas';
        $perPage = 10;
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('getPending')
            ->once()
            ->with($search, $perPage)
            ->andReturn($paginator);

        $result = $this->service->getPendingMosques($search, $perPage);

        $this->assertSame($paginator, $result);
    }

    /**
     * Test getPendingMosques passes null search to repository when no search given.
     *
     * Requirements: 4.1
     */
    public function test_get_pending_mosques_passes_null_search_to_repository(): void
    {
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('getPending')
            ->once()
            ->with(null, 15)
            ->andReturn($paginator);

        $result = $this->service->getPendingMosques(null);

        $this->assertSame($paginator, $result);
    }

    /**
     * Test getPendingMosques uses default perPage of 15 when not specified.
     *
     * Requirements: 4.1
     */
    public function test_get_pending_mosques_uses_default_per_page_of_15(): void
    {
        $paginator = $this->emptyPaginator();

        $this->mosqueRepository
            ->shouldReceive('getPending')
            ->once()
            ->with('search term', 15)
            ->andReturn($paginator);

        $result = $this->service->getPendingMosques('search term');

        $this->assertSame($paginator, $result);
    }

    // -------------------------------------------------------------------------
    // getMosqueDetail() — Requirements: 5.1
    // -------------------------------------------------------------------------

    /**
     * Test getMosqueDetail returns mosque when found.
     *
     * Requirements: 5.1
     */
    public function test_get_mosque_detail_returns_mosque_when_found(): void
    {
        $mosque = Mockery::mock(Mosque::class);

        $this->mosqueRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(42, ['admin', 'members'])
            ->andReturn($mosque);

        $result = $this->service->getMosqueDetail(42);

        $this->assertSame($mosque, $result);
    }

    /**
     * Test getMosqueDetail throws ModelNotFoundException when mosque not found.
     *
     * Requirements: 5.1
     */
    public function test_get_mosque_detail_throws_model_not_found_exception_when_mosque_not_found(): void
    {
        $this->mosqueRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(999, ['admin', 'members'])
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Mosque not found');

        $this->service->getMosqueDetail(999);
    }

    /**
     * Test getMosqueDetail loads admin and members relationships.
     *
     * Requirements: 5.1
     */
    public function test_get_mosque_detail_loads_admin_and_members_relations(): void
    {
        $mosque = Mockery::mock(Mosque::class);

        $this->mosqueRepository
            ->shouldReceive('findWithRelations')
            ->once()
            ->with(1, Mockery::on(function (array $relations) {
                return in_array('admin', $relations) && in_array('members', $relations);
            }))
            ->andReturn($mosque);

        $result = $this->service->getMosqueDetail(1);

        $this->assertSame($mosque, $result);
    }

    // -------------------------------------------------------------------------
    // getDaysWaiting() — Requirements: 4.4
    // -------------------------------------------------------------------------

    /**
     * Test getDaysWaiting returns correct number of days for a pending mosque.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_calculates_correctly_for_pending_mosque(): void
    {
        $createdAt = Carbon::now()->subDays(5);
        $mosque = $this->makeMosque(MosqueStatus::Pending, $createdAt);

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(5, $result);
    }

    /**
     * Test getDaysWaiting returns 0 for a mosque with active status.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_returns_0_for_active_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Active, Carbon::now()->subDays(10));

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(0, $result);
    }

    /**
     * Test getDaysWaiting returns 0 for a mosque with suspended status.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_returns_0_for_suspended_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Suspended, Carbon::now()->subDays(3));

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(0, $result);
    }

    /**
     * Test getDaysWaiting returns 0 for a mosque with rejected status.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_returns_0_for_rejected_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Rejected, Carbon::now()->subDays(7));

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(0, $result);
    }

    /**
     * Test getDaysWaiting returns 0 for a pending mosque created just now.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_returns_0_for_pending_mosque_created_today(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Pending, Carbon::now());

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(0, $result);
    }

    /**
     * Test getDaysWaiting returns correct days for pending mosque waiting over 7 days.
     *
     * Requirements: 4.4
     */
    public function test_get_days_waiting_calculates_correctly_for_long_waiting_pending_mosque(): void
    {
        $createdAt = Carbon::now()->subDays(14);
        $mosque = $this->makeMosque(MosqueStatus::Pending, $createdAt);

        $result = $this->service->getDaysWaiting($mosque);

        $this->assertEquals(14, $result);
    }
}
