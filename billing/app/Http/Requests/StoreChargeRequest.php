<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'encounter_external_id' => ['nullable', 'string', 'max:100'],
            'service_date' => ['required', 'date'],
            'cpt_code' => ['nullable', 'string', 'max:10', 'required_without:hcpcs_code'],
            'hcpcs_code' => ['nullable', 'string', 'max:10', 'required_without:cpt_code'],
            'modifier_1' => ['nullable', 'string', 'max:5'],
            'modifier_2' => ['nullable', 'string', 'max:5'],
            'units' => ['nullable', 'integer', 'min:1'],
            'charge_amount' => ['required', 'numeric', 'min:0'],
            'diagnosis_pointers' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
