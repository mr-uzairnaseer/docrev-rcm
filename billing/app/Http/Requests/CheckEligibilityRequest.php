<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckEligibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'payer_id' => ['required', 'integer', 'exists:payers,id'],
            'service_date' => ['required', 'date'],
            'member_id' => ['nullable', 'string', 'max:50'],
        ];
    }
}
