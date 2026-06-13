<?php

namespace Tests\Unit\Services;

// Feature: day11-mosque-suspend-reactivate, Property 5

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use App\Services\MosqueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Property 5: Atomisitas transaksi — tidak ada perubahan parsial
 *
 * **Validates: Requirements 1.9, 2.9, 8.3, 8.4**
 *
 * For any suspend or reactivate operation where the repository throws an exception
 * during update(), the database transaction MUST be rolled back completely.
 * DB::commit() MUST never be called, and the exception MUST be re-thrown.
 *
 * Feature: day11-mosque-suspend-reactivate, Property 5
 */
class MosqueServiceProperty5Test extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a mock Mosque model with the given status.
     */
    private function makeMosque(MosqueStatus $status): Mosque
    {
        $mosque = Mockery::mock(Mosque::class)->makePartial();
        $mosque->status = $status;
        $mosque->created_at = now();

        return $mosque;
    }

    // -------------------------------------------------------------------------
    // Property 5a: Suspend — rollback sempurna saat update() melempar exception
    // Validates: Requirements 1.9, 8.3, 8.4
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 1.9, 8.3, 8.4**
     *
     * Property 5: For any active mosque, if mosqueRepository->update() throws an
     * exception during suspend(), DB::rollBack() MUST be called and DB::commit()
     * MUST never be called — ensuring no partial changes are persisted.
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_suspend_rolls_back_transaction_when_update_throws_exception(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        $exceptionMessages = [
            'Connection lost during suspend',
            'Deadlock detected',
            'Disk quota exceeded',
            'Query timeout',
            'Foreign key constraint failed',
        ];

        for ($i = 0; $i < 20; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);
            $exceptionMessage = $exceptionMessages[$i % count($exceptionMessages)];

            $mosque = $this->makeMosque(MosqueStatus::Active);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andReturn($mosque);

            $repo->shouldReceive('update')
                ->once()
                ->andThrow(new \Exception($exceptionMessage));

            // Assert transaction atomicity: rollBack called, commit never called
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->suspend($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: suspend() MUST re-throw the exception when update() fails"
            );
            $this->assertSame(
                $exceptionMessage,
                $caughtException->getMessage(),
                "Iteration $i: re-thrown exception message must match original"
            );

            Mockery::close();
        }
    }

    /**
     * **Validates: Requirements 1.9, 8.3, 8.4**
     *
     * Property 5 (variant): If mosqueRepository->update() returns false (update failed
     * without throwing), suspend() MUST throw an exception, rollBack MUST be called,
     * and commit MUST never be called.
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_suspend_rolls_back_transaction_when_update_returns_false(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        for ($i = 0; $i < 20; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);

            $mosque = $this->makeMosque(MosqueStatus::Active);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andReturn($mosque);

            $repo->shouldReceive('update')
                ->once()
                ->andReturn(false);

            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->suspend($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: suspend() MUST throw when update() returns false"
            );
            $this->assertSame(
                'Failed to update mosque',
                $caughtException->getMessage(),
                "Iteration $i: exception message should be 'Failed to update mosque'"
            );

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 5b: Reactivate — rollback sempurna saat update() melempar exception
    // Validates: Requirements 2.9, 8.3, 8.4
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 2.9, 8.3, 8.4**
     *
     * Property 5: For any suspended mosque, if mosqueRepository->update() throws an
     * exception during reactivate(), DB::rollBack() MUST be called and DB::commit()
     * MUST never be called — ensuring no partial changes are persisted.
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_reactivate_rolls_back_transaction_when_update_throws_exception(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        $exceptionMessages = [
            'Connection lost during reactivate',
            'Deadlock detected',
            'Disk quota exceeded',
            'Query timeout',
            'Foreign key constraint failed',
        ];

        for ($i = 0; $i < 20; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);
            $exceptionMessage = $exceptionMessages[$i % count($exceptionMessages)];

            $mosque = $this->makeMosque(MosqueStatus::Suspended);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andReturn($mosque);

            $repo->shouldReceive('update')
                ->once()
                ->andThrow(new \Exception($exceptionMessage));

            // Assert transaction atomicity: rollBack called, commit never called
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->reactivate($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: reactivate() MUST re-throw the exception when update() fails"
            );
            $this->assertSame(
                $exceptionMessage,
                $caughtException->getMessage(),
                "Iteration $i: re-thrown exception message must match original"
            );

            Mockery::close();
        }
    }

    /**
     * **Validates: Requirements 2.9, 8.3, 8.4**
     *
     * Property 5 (variant): If mosqueRepository->update() returns false during
     * reactivate(), the service MUST throw an exception, rollBack MUST be called,
     * and commit MUST never be called.
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_reactivate_rolls_back_transaction_when_update_returns_false(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        for ($i = 0; $i < 20; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);

            $mosque = $this->makeMosque(MosqueStatus::Suspended);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andReturn($mosque);

            $repo->shouldReceive('update')
                ->once()
                ->andReturn(false);

            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->reactivate($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: reactivate() MUST throw when update() returns false"
            );
            $this->assertSame(
                'Failed to update mosque',
                $caughtException->getMessage(),
                "Iteration $i: exception message should be 'Failed to update mosque'"
            );

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 5c: find() melempar exception — rollback tetap dipanggil
    // Validates: Requirements 8.3, 8.4
    // -------------------------------------------------------------------------

    /**
     * **Validates: Requirements 8.3, 8.4**
     *
     * Property 5: Even if find() throws an exception (before update is reached),
     * the service MUST still call DB::rollBack() and MUST NOT call DB::commit().
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_suspend_rolls_back_transaction_when_find_throws_exception(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        for ($i = 0; $i < 10; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andThrow(new \Exception('Database connection failed'));

            $repo->shouldReceive('update')->never();

            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->suspend($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: suspend() MUST re-throw exception from find()"
            );

            Mockery::close();
        }
    }

    /**
     * **Validates: Requirements 8.3, 8.4**
     *
     * Property 5: Even if find() throws an exception during reactivate(),
     * the service MUST still call DB::rollBack() and MUST NOT call DB::commit().
     *
     * Feature: day11-mosque-suspend-reactivate, Property 5
     */
    public function test_property5_reactivate_rolls_back_transaction_when_find_throws_exception(): void
    {
        // Feature: day11-mosque-suspend-reactivate, Property 5
        for ($i = 0; $i < 10; $i++) {
            $mosqueId = rand(1, 10000);
            $userId = rand(1, 500);

            /** @var MosqueRepositoryInterface $repo */
            $repo = Mockery::mock(MosqueRepositoryInterface::class);
            $repo->shouldReceive('find')
                ->once()
                ->with($mosqueId)
                ->andThrow(new \Exception('Database connection failed'));

            $repo->shouldReceive('update')->never();

            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('rollBack')->once();
            DB::shouldReceive('commit')->never();

            $service = new MosqueService($repo);

            $caughtException = null;
            try {
                $service->reactivate($mosqueId, $userId);
            } catch (\Exception $e) {
                $caughtException = $e;
            }

            $this->assertNotNull(
                $caughtException,
                "Iteration $i: reactivate() MUST re-throw exception from find()"
            );

            Mockery::close();
        }
    }
}
