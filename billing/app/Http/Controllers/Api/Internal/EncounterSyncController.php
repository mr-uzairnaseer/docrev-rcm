<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncounterSyncController extends Controller
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
            'encounter.uuid' => ['required', 'uuid'],
            'encounter.encounter_date' => ['required', 'date'],
            'encounter.provider_npi' => ['required', 'string', 'size:10'],
            'encounter.place_of_service' => ['nullable', 'string'],
            'diagnoses' => ['required', 'array', 'min:1'],
            'diagnoses.*' => ['string', 'max:10'],
            'charges' => ['required', 'array', 'min:1'],
            'charges.*.cpt_code' => ['nullable', 'string', 'max:10'],
            'charges.*.hcpcs_code' => ['nullable', 'string', 'max:10'],
            'charges.*.modifier_1' => ['nullable', 'string', 'max:5'],
            'charges.*.modifier_2' => ['nullable', 'string', 'max:5'],
            'charges.*.units' => ['nullable', 'integer', 'min:1'],
            'charges.*.charge_amount' => ['required', 'numeric'],
            'charges.*.service_date' => ['required', 'date'],
            'charges.*.diagnosis_pointers' => ['nullable', 'array'],
        ]);

        $result = DB::transaction(function () use ($validated) {
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
                    'first_name' => $validated['patient']['first_name'],
                    'last_name' => $validated['patient']['last_name'],
                    'date_of_birth' => $validated['patient']['date_of_birth'],
                    'gender' => $validated['patient']['gender'] ?? null,
                    'mrn' => $validated['patient']['mrn'] ?? null,
                ]
            );

            $createdCharges = [];

            foreach ($validated['charges'] as $chargeData) {
                $existing = Charge::where('organization_id', $organization->id)
                    ->where('encounter_external_id', $validated['encounter']['uuid'])
                    ->where('cpt_code', $chargeData['cpt_code'] ?? null)
                    ->where('hcpcs_code', $chargeData['hcpcs_code'] ?? null)
                    ->where('charge_amount', $chargeData['charge_amount'])
                    ->first();

                if ($existing) {
                    $createdCharges[] = $existing;
                    continue;
                }

                $createdCharges[] = Charge::create([
                    'organization_id' => $organization->id,
                    'patient_id' => $patient->id,
                    'encounter_external_id' => $validated['encounter']['uuid'],
                    'service_date' => $chargeData['service_date'],
                    'cpt_code' => $chargeData['cpt_code'] ?? null,
                    'hcpcs_code' => $chargeData['hcpcs_code'] ?? null,
                    'modifier_1' => $chargeData['modifier_1'] ?? null,
                    'modifier_2' => $chargeData['modifier_2'] ?? null,
                    'units' => $chargeData['units'] ?? 1,
                    'charge_amount' => $chargeData['charge_amount'],
                    'diagnosis_pointers' => $chargeData['diagnosis_pointers'] ?? [1],
                    'icd10_codes' => $validated['diagnoses'],
                    'status' => Charge::STATUS_READY,
                    'notes' => 'Synced from EHR encounter '.$validated['encounter']['uuid'],
                ]);
            }

            return [
                'organization_id' => $organization->id,
                'patient_id' => $patient->id,
                'patient_uuid' => $patient->uuid,
                'charge_ids' => collect($createdCharges)->pluck('id')->values()->all(),
                'encounter_uuid' => $validated['encounter']['uuid'],
            ];
        });

        return response()->json([
            'message' => 'Encounter synced to billing.',
            'data' => $result,
        ], 201);
    }
}
