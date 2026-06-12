<?php

namespace App\Http\Requests\Owner;

use App\Enums\MosqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveMosqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by the 'owner' middleware on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::Pending->value),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID wajib diisi.',
            'mosque_id.integer' => 'Mosque ID harus berupa angka.',
            'mosque_id.exists' => 'Masjid tidak ditemukan atau tidak dalam status menunggu persetujuan.',
        ];
    }
}
