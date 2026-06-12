<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Events\MosqueApproved;
use App\Events\MosqueRejected;
use App\Models\Mosque;
use App\Services\MosqueService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for MosqueService.
 *
 * Requirements: 3.1, 4.1, 5.1, 4.4, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
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

    /**
     * Create a mock Mosque model that also handles the refresh() call made by the service.
     */
    private function makeMosqueWithRefresh(MosqueStatus $status): Mosque
    {
        $mosque = Mockery::mock(Mosque::class)->makePartial();
        $mosque->status = $status;
        $mosque->created_at = now();
        $mosque->shouldReceive('refresh')->once()->andReturnSelf();

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

    // -------------------------------------------------------------------------
    // generateUniqueInvitationCode() — tested indirectly via approve()
    // Requirements: 1.5, 1.6
    // -------------------------------------------------------------------------

    /**
     * Test generateUniqueInvitationCode retries when code already exists, returning a unique code.
     *
     * Requirements: 1.5, 1.6
     */
    public function test_approve_retries_invitation_code_generation_when_code_already_exists(): void
    {
        Event::fake();

        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository
            ->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($mosque);

        // First call returns true (code taken), second returns false (code is unique)
        $this->mosqueRepository
            ->shouldReceive('existsByInvitationCode')
            ->twice()
            ->andReturn(true, false);

        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) {
                return isset($data['invitation_code'])
                    && strlen($data['invitation_code']) === 6
                    && strtoupper($data['invitation_code']) === $data['invitation_code'];
            }))
            ->andReturn(true);

        $result = $this->service->approve(1, 99);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // approve() — Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9
    // -------------------------------------------------------------------------

    /**
     * Test approve updates mosque status to ACTIVE.
     *
     * Requirements: 1.3
     */
    public function test_approve_updates_mosque_status_to_active(): void
    {
        Event::fake();

        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('existsByInvitationCode')->once()->andReturn(false);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) {
                return $data['status'] === MosqueStatus::Active;
            }))
            ->andReturn(true);

        $result = $this->service->approve(1, 99);

        $this->assertTrue($result);
    }

    /**
     * Test approve generates and saves a 6-character uppercase alphanumeric invitation code.
     *
     * Requirements: 1.5, 1.6
     */
    public function test_approve_generates_and_saves_invitation_code(): void
    {
        Event::fake();

        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('existsByInvitationCode')->once()->andReturn(false);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) {
                return isset($data['invitation_code'])
                    && strlen($data['invitation_code']) === 6
                    && preg_match('/^[A-Z0-9]{6}$/', $data['invitation_code']) === 1;
            }))
            ->andReturn(true);

        $result = $this->service->approve(1, 99);

        $this->assertTrue($result);
    }

    /**
     * Test approve records approved_by and approved_at timestamp.
     *
     * Requirements: 1.7, 1.8, 1.9
     */
    public function test_approve_records_approved_by_and_approved_at(): void
    {
        Event::fake();

        $userId = 42;
        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('existsByInvitationCode')->once()->andReturn(false);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) use ($userId) {
                return $data['approved_by'] === $userId
                    && isset($data['approved_at']);
            }))
            ->andReturn(true);

        $result = $this->service->approve(1, $userId);

        $this->assertTrue($result);
    }

    /**
     * Test approve dispatches MosqueApproved event with correct data.
     *
     * Requirements: 1.4, 1.10
     */
    public function test_approve_dispatches_mosque_approved_event(): void
    {
        Event::fake();

        $userId = 42;
        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('existsByInvitationCode')->once()->andReturn(false);
        $this->mosqueRepository->shouldReceive('update')->once()->andReturn(true);

        $this->service->approve(1, $userId);

        Event::assertDispatched(MosqueApproved::class, function (MosqueApproved $event) use ($userId) {
            return $event->approvedByUserId === $userId
                && $event->mosque instanceof Mosque;
        });
    }

    /**
     * Test approve throws exception when mosque is not found.
     *
     * Requirements: 1.12
     */
    public function test_approve_throws_exception_when_mosque_not_found(): void
    {
        $this->mosqueRepository->shouldReceive('find')->once()->with(999)->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->approve(999, 1);
    }

    /**
     * Test approve throws exception when mosque status is not pending (Active).
     *
     * Requirements: 1.12
     */
    public function test_approve_throws_exception_for_active_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Active);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->approve(1, 1);
    }

    /**
     * Test approve throws exception when mosque status is not pending (Rejected).
     *
     * Requirements: 1.12
     */
    public function test_approve_throws_exception_for_rejected_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Rejected);

        $this->mosqueRepository->shouldReceive('find')->once()->with(2)->andReturn($mosque);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->approve(2, 1);
    }

    /**
     * Test approve rolls back database transaction on repository update failure.
     *
     * Requirements: 7.1, 7.2
     */
    public function test_approve_rolls_back_transaction_on_update_failure(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('existsByInvitationCode')->once()->andReturn(false);
        $this->mosqueRepository->shouldReceive('update')->once()->andReturn(false);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update mosque');

        $this->service->approve(1, 1);
    }

    // -------------------------------------------------------------------------
    // reject() — Requirements: 2.4, 2.5, 2.6, 2.7, 2.8, 2.9
    // -------------------------------------------------------------------------

    /**
     * Test reject updates mosque status to REJECTED.
     *
     * Requirements: 2.4
     */
    public function test_reject_updates_mosque_status_to_rejected(): void
    {
        Event::fake();

        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) {
                return $data['status'] === MosqueStatus::Rejected;
            }))
            ->andReturn(true);

        $result = $this->service->reject(1, 'This mosque did not meet our requirements.', 99);

        $this->assertTrue($result);
    }

    /**
     * Test reject saves the rejection reason.
     *
     * Requirements: 2.5
     */
    public function test_reject_saves_rejection_reason(): void
    {
        Event::fake();

        $reason = 'Documents are incomplete and invalid.';
        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) use ($reason) {
                return $data['rejection_reason'] === $reason;
            }))
            ->andReturn(true);

        $result = $this->service->reject(1, $reason, 99);

        $this->assertTrue($result);
    }

    /**
     * Test reject records rejected_by and rejected_at timestamp.
     *
     * Requirements: 2.6, 2.7, 2.8
     */
    public function test_reject_records_rejected_by_and_rejected_at(): void
    {
        Event::fake();

        $userId = 55;
        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository
            ->shouldReceive('update')
            ->once()
            ->with(1, Mockery::on(function (array $data) use ($userId) {
                return $data['rejected_by'] === $userId
                    && isset($data['rejected_at']);
            }))
            ->andReturn(true);

        $result = $this->service->reject(1, 'Documents are incomplete and missing key details.', $userId);

        $this->assertTrue($result);
    }

    /**
     * Test reject dispatches MosqueRejected event with correct data.
     *
     * Requirements: 2.9, 2.10
     */
    public function test_reject_dispatches_mosque_rejected_event(): void
    {
        Event::fake();

        $userId = 55;
        $reason = 'Location is outside the service area.';
        $mosque = $this->makeMosqueWithRefresh(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('update')->once()->andReturn(true);

        $this->service->reject(1, $reason, $userId);

        Event::assertDispatched(MosqueRejected::class, function (MosqueRejected $event) use ($userId, $reason) {
            return $event->rejectedByUserId === $userId
                && $event->rejectionReason === $reason
                && $event->mosque instanceof Mosque;
        });
    }

    /**
     * Test reject throws exception when mosque is not found.
     *
     * Requirements: 2.12
     */
    public function test_reject_throws_exception_when_mosque_not_found(): void
    {
        $this->mosqueRepository->shouldReceive('find')->once()->with(999)->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->reject(999, 'Some reason here.', 1);
    }

    /**
     * Test reject throws exception when mosque status is not pending (Active).
     *
     * Requirements: 2.12
     */
    public function test_reject_throws_exception_for_active_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Active);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->reject(1, 'Some reason here.', 1);
    }

    /**
     * Test reject throws exception when mosque status is not pending (already Rejected).
     *
     * Requirements: 2.12
     */
    public function test_reject_throws_exception_for_already_rejected_mosque(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Rejected);

        $this->mosqueRepository->shouldReceive('find')->once()->with(3)->andReturn($mosque);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid mosque or status is not pending');

        $this->service->reject(3, 'Some reason here.', 1);
    }

    /**
     * Test reject rolls back database transaction on repository update failure.
     *
     * Requirements: 7.1, 7.2
     */
    public function test_reject_rolls_back_transaction_on_update_failure(): void
    {
        $mosque = $this->makeMosque(MosqueStatus::Pending);

        $this->mosqueRepository->shouldReceive('find')->once()->with(1)->andReturn($mosque);
        $this->mosqueRepository->shouldReceive('update')->once()->andReturn(false);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update mosque');

        $this->service->reject(1, 'Some reason here.', 1);
    }
}
