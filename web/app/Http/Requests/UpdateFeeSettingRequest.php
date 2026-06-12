<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeeSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only super-admin (owner) can update platform settings
        return $this->user()?->hasRole('super-admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'fee_percentage' => [
                'required',
                'integer',
                'min:0',
                'max:10000', // Max 100% (10000 basis points)
            ],
            'fee_mechanism' => [
                'required',
                'string',
                Rule::in(['added_to_donor', 'deducted_from_donation']),
            ],
            'fee_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'fee_percentage' => 'persentase fee',
            'fee_mechanism' => 'mekanisme fee',
            'fee_active' => 'status fee',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fee_percentage.required' => 'Persentase fee wajib diisi.',
            'fee_percentage.integer' => 'Persentase fee harus berupa angka.',
            'fee_percentage.min' => 'Persentase fee minimal 0.',
            'fee_percentage.max' => 'Persentase fee maksimal 100% (10000 basis poin).',
            'fee_mechanism.required' => 'Mekanisme fee wajib dipilih.',
            'fee_mechanism.in' => 'Mekanisme fee tidak valid.',
            'fee_active.required' => 'Status fee wajib dipilih.',
            'fee_active.boolean' => 'Status fee harus berupa boolean.',
        ];
    }
}
