<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'array'],
            'mrn' => ['nullable', 'string', 'max:50'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'allergies' => ['nullable', 'string'],
        ];
    }
}
