<?php

namespace App\Http\Requests;

use App\Models\Encounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['sometimes', 'integer', 'exists:providers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'encounter_date' => ['sometimes', 'date'],
            'encounter_type' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in([
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
