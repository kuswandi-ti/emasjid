<?php

namespace App\DataTransferObjects;

use App\Enums\MosqueStatus;
use App\Models\Mosque;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RejectMosqueDTO
{
    /**
     * Create a new RejectMosqueDTO instance.
     *
     * @param int $mosque_id The ID of the mosque to reject
     * @param int $rejected_by_user_id The ID of the user rejecting the mosque
     * @param string $rejection_reason The reason for rejection
     * @param Carbon $rejected_at The timestamp when the mosque was rejected
     */
    public function __construct(
        public readonly int $mosque_id,
        public readonly int $rejected_by_user_id,
        public readonly string $rejection_reason,
        public readonly Carbon $rejected_at
    ) {}

    /**
     * Create a new instance from an array of data with validation.
     *
     * @param array<string, mixed> $data
     * @return self
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $validator = Validator::make($data, [
            'mosque_id' => [
                'required',
                'integer',
                'exists:mosques,id',
                function ($attribute, $value, $fail) {
                    $mosque = Mosque::find($value);
                    if ($mosque && $mosque->status !== MosqueStatus::Pending) {
                        $fail('The mosque must have pending status to be rejected.');
                    }
                },
            ],
            'rejected_by_user_id' => 'required|integer|exists:users,id',
            'rejection_reason' => 'required|string|min:10',
            'rejected_at' => 'nullable|date',
        ], [
            'mosque_id.required' => 'Mosque ID is required.',
            'mosque_id.integer' => 'Mosque ID must be an integer.',
            'mosque_id.exists' => 'The selected mosque does not exist.',
            'rejected_by_user_id.required' => 'Rejected by user ID is required.',
            'rejected_by_user_id.integer' => 'Rejected by user ID must be an integer.',
            'rejected_by_user_id.exists' => 'The selected user does not exist.',
            'rejection_reason.required' => 'Rejection reason is required.',
            'rejection_reason.string' => 'Rejection reason must be a string.',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters.',
            'rejected_at.date' => 'Rejected at must be a valid date.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            mosque_id: $validated['mosque_id'],
            rejected_by_user_id: $validated['rejected_by_user_id'],
            rejection_reason: $validated['rejection_reason'],
            rejected_at: isset($validated['rejected_at'])
                ? Carbon::parse($validated['rejected_at'])
                : Carbon::now()
        );
    }

    /**
     * Create a new instance from request data.
     *
     * @param \Illuminate\Http\Request $request
     * @return self
     * @throws ValidationException
     */
    public static function fromRequest($request): self
    {
        return self::fromArray($request->all());
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mosque_id' => $this->mosque_id,
            'rejected_by_user_id' => $this->rejected_by_user_id,
            'rejection_reason' => $this->rejection_reason,
            'rejected_at' => $this->rejected_at,
        ];
    }
}
