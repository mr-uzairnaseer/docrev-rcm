<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuildClaimRequest extends FormRequest
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
            'charge_ids' => ['required', 'array', 'min:1'],
            'charge_ids.*' => ['integer', 'exists:charges,id'],
            'icd10_codes' => ['required', 'array', 'min:1'],
            'icd10_codes.*' => ['string', 'max:10'],
            'rendering_provider_npi' => ['required', 'string', 'size:10'],
            'billing_provider_npi' => ['required', 'string', 'size:10'],
            'place_of_service' => ['nullable', 'string', 'max:5'],
        ];
    }
}
