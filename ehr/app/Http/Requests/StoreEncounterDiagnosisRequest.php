<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEncounterDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'icd10_code' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
            'sequence' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
