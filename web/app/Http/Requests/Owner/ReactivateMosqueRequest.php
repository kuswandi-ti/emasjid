<?php

namespace App\Http\Requests\Owner;

use App\Enums\MosqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactivateMosqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')->where('status', MosqueStatus::Suspended->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mosque_id.required' => 'Mosque ID wajib diisi.',
            'mosque_id.integer'  => 'Mosque ID harus berupa angka.',
            'mosque_id.exists'   => 'Masjid tidak dapat diaktifkan kembali karena statusnya bukan ditangguhkan.',
        ];
    }
}
