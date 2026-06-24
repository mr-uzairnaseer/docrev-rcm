<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'encounter_date' => ['required', 'date'],
            'encounter_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in([
                Encounter::STATUS_SCHEDULED,
                Encounter::STATUS_IN_PROGRESS,
                Encounter::STATUS_COMPLETED,
                Encounter::STATUS_CANCELLED,
            ])],
            'chief_complaint' => ['nullable', 'string'],
            'clinical_notes' => ['nullable', 'string'],
        ];
    }
}
