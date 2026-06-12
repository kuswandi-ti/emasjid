<?php

namespace App\DataTransferObjects;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Data Transfer Object for Mosque Approval
 * 
 * Validates Requirements 6.1, 6.3:
 * - mosque_id must exist and refer to a mosque with 'pending' status
 * - approved_by_user_id must be provided (super-admin user)
 * - approved_at timestamp
 */
class ApproveMosqueDTO
{
    /**
     * Create a new ApproveMosqueDTO instance
     *
     * @param int $mosque_id The ID of the mosque to approve
     * @param int $approved_by_user_id The ID of the user approving the mosque
     * @param Carbon $approved_at The timestamp when the mosque was approved
     */
    public function __construct(
        public readonly int $mosque_id,
        public readonly int $approved_by_user_id,
        public readonly Carbon $approved_at
    ) {
    }

    /**
     * Create DTO from array with validation
     *
     * @param array $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        // Validate mosque_id
        if (!isset($data['mosque_id']) || !is_numeric($data['mosque_id'])) {
            throw ValidationException::withMessages([
                'mosque_id' => ['The mosque_id field is required and must be an integer.']
            ]);
        }

        // Validate mosque exists
        $mosque = Mosque::find($data['mosque_id']);
        if (!$mosque) {
            throw ValidationException::withMessages([
                'mosque_id' => ['The selected mosque does not exist.']
            ]);
        }

        // Validate mosque has pending status
        if ($mosque->status !== MosqueStatus::Pending) {
            throw ValidationException::withMessages([
                'mosque_id' => ['Only pending mosques can be approved. Current status: ' . $mosque->status->value]
            ]);
        }

        // Validate approved_by_user_id
        if (!isset($data['approved_by_user_id']) || !is_numeric($data['approved_by_user_id'])) {
            throw ValidationException::withMessages([
                'approved_by_user_id' => ['The approved_by_user_id field is required and must be an integer.']
            ]);
        }

        // Set or validate approved_at timestamp
        $approved_at = isset($data['approved_at']) 
            ? Carbon::parse($data['approved_at']) 
            : Carbon::now();

        return new self(
            mosque_id: (int) $data['mosque_id'],
            approved_by_user_id: (int) $data['approved_by_user_id'],
            approved_at: $approved_at
        );
    }

    /**
     * Create DTO from request
     *
     * @param int $mosque_id
     * @param int $approved_by_user_id
     * @return self
     * @throws ValidationException
     */
    public static function fromRequest(int $mosque_id, int $approved_by_user_id): self
    {
        return self::fromArray([
            'mosque_id' => $mosque_id,
            'approved_by_user_id' => $approved_by_user_id,
            'approved_at' => Carbon::now(),
        ]);
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'mosque_id' => $this->mosque_id,
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_at' => $this->approved_at,
        ];
    }
}
