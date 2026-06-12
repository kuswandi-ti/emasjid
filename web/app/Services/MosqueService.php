<?php

namespace App\Services;

use App\Contracts\Repositories\MosqueRepositoryInterface;
use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
}
