<?php

namespace App\Http\Requests\Owner;

use App\Enums\MosqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectMosqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by middleware (auth + owner role).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Requirements: 3.4, 3.5, 3.6, 3.7
     */
    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::Pending->value),
            ],
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ];
    }

    /**
     * Get the custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'mosque_id.required' => 'ID masjid wajib diisi.',
            'mosque_id.integer'  => 'ID masjid harus berupa angka.',
            'mosque_id.exists'   => 'Masjid tidak ditemukan atau statusnya bukan pending.',
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.string'   => 'Alasan penolakan harus berupa teks.',
            'rejection_reason.min'      => 'Alasan penolakan minimal harus 10 karakter.',
            'rejection_reason.max'      => 'Alasan penolakan tidak boleh melebihi 1000 karakter.',
        ];
    }
}
