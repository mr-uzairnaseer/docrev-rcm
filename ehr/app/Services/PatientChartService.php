<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\PatientAllergyItem;
use App\Models\PatientCareTeamMember;
use App\Models\PatientDocument;
use App\Models\PatientInsurance;
use App\Models\PatientProblem;
use App\Models\PatientVital;
use App\Models\Prescription;
use App\Models\Provider;

class PatientChartService
{
    public function chart(Patient $patient, array $filters = []): array
    {
        $patient->load([
            'problems' => fn ($q) => $q->orderBy('rank'),
            'insurances',
            'careTeamMembers',
            'vitals' => fn ($q) => $q->orderByDesc('recorded_at')->limit(20),
            'documents' => function ($q) use ($filters) {
                $q->orderByDesc('created_at');
                if (! empty($filters['document_type'])) {
                    $q->where('document_type', $filters['document_type']);
                }
                if (! empty($filters['document_date'])) {
                    $q->whereDate('created_at', $filters['document_date']);
                }
                if (! empty($filters['uploaded_by'])) {
                    $q->where('uploaded_by', $filters['uploaded_by']);
                }
            },
            'allergyItems',
        ]);

        $medications = Prescription::forOrganization($patient->organization_id)
            ->where('patient_id', $patient->id)
            ->whereIn('status', [Prescription::STATUS_SENT, Prescription::STATUS_DRAFT])
            ->with('provider')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Prescription $rx) => [
                'id' => $rx->id,
                'drug_name' => $rx->drug_name,
                'sig' => $rx->sig,
                'refills' => $rx->refills,
                'status' => $rx->status,
                'prescriber' => $rx->provider
                    ? trim($rx->provider->first_name.' '.$rx->provider->last_name)
                    : null,
            ]);

        $visits = Encounter::forOrganization($patient->organization_id)
            ->where('patient_id', $patient->id)
            ->with('provider')
            ->orderByDesc('encounter_date')
            ->get()
            ->map(fn (Encounter $enc) => [
                'id' => $enc->id,
                'encounter_date' => $enc->encounter_date?->toIso8601String(),
                'status' => $enc->status,
                'billing_sync_status' => $enc->billing_sync_status,
                'provider' => $enc->provider
                    ? trim($enc->provider->first_name.' '.$enc->provider->last_name)
                    : null,
            ]);

        return [
            'patient' => [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'mrn' => $patient->mrn,
                'allergies_summary' => $patient->allergies,
            ],
            'problems' => $patient->problems,
            'insurances' => $patient->insurances,
            'care_team' => $patient->careTeamMembers,
            'vitals' => $patient->vitals,
            'documents' => $patient->documents,
            'allergies' => $patient->allergyItems,
            'medications' => $medications,
            'visits' => $visits,
        ];
    }

    public function verifyEligibility(PatientInsurance $insurance): PatientInsurance
    {
        $insurance->update([
            'coverage_status' => 'active',
            'copay_amount' => $insurance->copay_amount ?? 20.00,
            'last_verified_at' => now(),
        ]);

        return $insurance->fresh();
    }

    public function addProblem(Patient $patient, array $data): PatientProblem
    {
        return PatientProblem::create(array_merge($data, [
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
        ]));
    }

    public function addVital(Patient $patient, array $data): PatientVital
    {
        return PatientVital::create(array_merge($data, [
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]));
    }

    public function addDocument(Patient $patient, array $data, ?int $userId): PatientDocument
    {
        return PatientDocument::create(array_merge($data, [
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
            'uploaded_by' => $userId,
        ]));
    }

    public function addAllergy(Patient $patient, array $data): PatientAllergyItem
    {
        return PatientAllergyItem::create(array_merge($data, [
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
        ]));
    }
}
