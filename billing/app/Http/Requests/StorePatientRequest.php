<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:20'],
            'mrn' => ['nullable', 'string', 'max:50'],
            'ehr_patient_uuid' => ['nullable', 'uuid'],
            'external_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
