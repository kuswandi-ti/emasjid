<?php

namespace App\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Events\MosqueApproved;
use App\Events\MosqueReactivated;
use App\Events\MosqueRejected;
use App\Events\MosqueSuspended;
use App\Models\Mosque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MosqueService
{
    public function __construct(
        protected MosqueRepositoryInterface $mosqueRepository
    ) {}

    /**
     * Get paginated mosque list with optional filters.
     *
     * @param  array  $filters  Supported keys: status, search, city
     * @param  int    $perPage  Number of records per page
     */
    public function getMosqueList(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->mosqueRepository->all($filters, $perPage);
    }

    /**
     * Get pending mosques sorted by oldest first with optional search.
     *
     * @param  string|null  $search  Optional search term for name, city, or admin email
     * @param  int          $perPage Number of records per page
     */
    public function getPendingMosques(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->mosqueRepository->getPending($search, $perPage);
    }

    /**
     * Get detailed mosque information with admin and members relationships.
     *
     * @param  int  $id  Mosque ID
     * @throws ModelNotFoundException If mosque not found
     */
    public function getMosqueDetail(int $id): Mosque
    {
        $mosque = $this->mosqueRepository->findWithRelations($id, [
            'admin',
            'members',
        ]);

        if (! $mosque) {
            throw new ModelNotFoundException('Mosque not found');
        }

        return $mosque;
    }

    /**
     * Calculate the number of days a mosque has been waiting for verification.
     * Returns 0 for non-pending mosques.
     *
     * @param  Mosque  $mosque  The mosque model instance
     */
    public function getDaysWaiting(Mosque $mosque): int
    {
        if ($mosque->status !== MosqueStatus::Pending) {
            return 0;
        }

        return $mosque->created_at->diffInDays(now());
    }

    /**
     * Approve a pending mosque: activate it, assign an invitation code, and dispatch MosqueApproved event.
     * All database changes are wrapped in a transaction to ensure consistency.
     *
     * @param  int  $mosqueId  The ID of the mosque to approve
     * @param  int  $userId    The ID of the owner performing the approval
     * @return bool True on success
     *
     * @throws \Exception If mosque is not found, not in pending status, or update fails
     */
    public function approve(int $mosqueId, int $userId): bool
    {
        DB::beginTransaction();

        try {
            $mosque = $this->mosqueRepository->find($mosqueId);

            if (! $mosque || $mosque->status !== MosqueStatus::Pending) {
                throw new \Exception('Invalid mosque or status is not pending');
            }

            $invitationCode = $this->generateUniqueInvitationCode();

            $updated = $this->mosqueRepository->update($mosqueId, [
                'status'          => MosqueStatus::Active,
                'invitation_code' => $invitationCode,
                'approved_at'     => now(),
                'approved_by'     => $userId,
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update mosque');
            }

            $mosque->refresh();

            event(new MosqueApproved($mosque, $userId, now()));

            DB::commit();

            Log::info('Mosque approved', [
                'mosque_id'   => $mosqueId,
                'approved_by' => $userId,
                'approved_at' => $mosque->approved_at,
            ]);

            Cache::forget('owner.dashboard.statistics');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a pending mosque: mark it as rejected, save the reason, and dispatch MosqueRejected event.
     * All database changes are wrapped in a transaction to ensure consistency.
     *
     * @param  int     $mosqueId  The ID of the mosque to reject
     * @param  string  $reason    The reason for rejection (min 10 characters)
     * @param  int     $userId    The ID of the owner performing the rejection
     * @return bool True on success
     *
     * @throws \Exception If mosque is not found, not in pending status, or update fails
     */
    public function reject(int $mosqueId, string $reason, int $userId): bool
    {
        DB::beginTransaction();

        try {
            $mosque = $this->mosqueRepository->find($mosqueId);

            if (! $mosque || $mosque->status !== MosqueStatus::Pending) {
                throw new \Exception('Invalid mosque or status is not pending');
            }

            $updated = $this->mosqueRepository->update($mosqueId, [
                'status'           => MosqueStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_at'      => now(),
                'rejected_by'      => $userId,
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update mosque');
            }

            $mosque->refresh();

            event(new MosqueRejected($mosque, $userId, $reason, now()));

            DB::commit();

            Log::info('Mosque rejected', [
                'mosque_id'   => $mosqueId,
                'rejected_by' => $userId,
                'rejected_at' => $mosque->rejected_at,
                'reason'      => $reason,
            ]);

            Cache::forget('owner.dashboard.statistics');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Suspend an active mosque: change its status to suspended and dispatch MosqueSuspended event.
     * All database changes are wrapped in a transaction to ensure consistency.
     *
     * @param  int  $mosqueId  The ID of the mosque to suspend
     * @param  int  $userId    The ID of the owner performing the suspension
     * @return bool True on success
     *
     * @throws \Exception If mosque is not found, not in active status, or update fails
     */
    public function suspend(int $mosqueId, int $userId): bool
    {
        DB::beginTransaction();

        try {
            $mosque = $this->mosqueRepository->find($mosqueId);

            if (! $mosque || $mosque->status !== MosqueStatus::Active) {
                throw new \Exception('Invalid mosque or status is not active');
            }

            $updated = $this->mosqueRepository->update($mosqueId, [
                'status' => MosqueStatus::Suspended,
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update mosque');
            }

            $mosque->refresh();

            event(new MosqueSuspended($mosque, $userId, now()));

            DB::commit();

            Log::info("Mosque suspended: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}");

            Cache::forget('owner.dashboard.statistics');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reactivate a suspended mosque: change its status back to active and dispatch MosqueReactivated event.
     * All database changes are wrapped in a transaction to ensure consistency.
     *
     * @param  int  $mosqueId  The ID of the mosque to reactivate
     * @param  int  $userId    The ID of the owner performing the reactivation
     * @return bool True on success
     *
     * @throws \Exception If mosque is not found, not in suspended status, or update fails
     */
    public function reactivate(int $mosqueId, int $userId): bool
    {
        DB::beginTransaction();

        try {
            $mosque = $this->mosqueRepository->find($mosqueId);

            if (! $mosque || $mosque->status !== MosqueStatus::Suspended) {
                throw new \Exception('Invalid mosque or status is not suspended');
            }

            $updated = $this->mosqueRepository->update($mosqueId, [
                'status' => MosqueStatus::Active,
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update mosque');
            }

            $mosque->refresh();

            event(new MosqueReactivated($mosque, $userId, now()));

            DB::commit();

            Log::info("Mosque reactivated: mosque_id={$mosque->id}, mosque_name={$mosque->name}, by_user_id={$userId}");

            Cache::forget('owner.dashboard.statistics');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique 6-character uppercase alphanumeric invitation code.
     * Uses a do-while loop to guarantee the code does not already exist in the mosques table.
     */
    private function generateUniqueInvitationCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while ($this->mosqueRepository->existsByInvitationCode($code));

        return $code;
    }
}
