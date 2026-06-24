<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientProvisionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization.slug' => ['required', 'string'],
            'organization.name' => ['required', 'string'],
            'organization.npi' => ['nullable', 'string'],
            'patient.uuid' => ['required', 'uuid'],
            'patient.first_name' => ['required', 'string'],
            'patient.last_name' => ['required', 'string'],
            'patient.date_of_birth' => ['required', 'date'],
            'patient.gender' => ['nullable', 'string'],
            'patient.mrn' => ['nullable', 'string'],
            'patient.email' => ['nullable', 'string'],
            'patient.phone' => ['nullable', 'string'],
        ]);

        $organization = Organization::firstOrCreate(
            ['slug' => $validated['organization']['slug']],
            [
                'name' => $validated['organization']['name'],
                'npi' => $validated['organization']['npi'] ?? null,
            ]
        );

        $patient = Patient::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'ehr_patient_uuid' => $validated['patient']['uuid'],
            ],
            [
                'uuid' => $validated['patient']['uuid'],
                'first_name' => $validated['patient']['first_name'],
                'last_name' => $validated['patient']['last_name'],
                'date_of_birth' => $validated['patient']['date_of_birth'],
                'gender' => $validated['patient']['gender'] ?? null,
                'mrn' => $validated['patient']['mrn'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Patient provisioned in billing.',
            'billing_patient_uuid' => $patient->uuid,
            'billing_patient_id' => $patient->id,
        ]);
    }
}
