<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientAllergyItem;
use App\Models\PatientProblem;
use App\Models\PatientVital;
use App\Models\Prescription;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    public function dashboard(): JsonResponse
    {
        $orgId = $this->organizationId();

        $encounters = Encounter::forOrganization($orgId);
        $synced = (clone $encounters)->where('billing_sync_status', 'synced')->count();
        $totalEncounters = $encounters->count();

        return response()->json([
            'data' => [
                'patients' => Patient::forOrganization($orgId)->count(),
                'encounters' => $totalEncounters,
                'appointments' => Appointment::forOrganization($orgId)->count(),
                'prescriptions' => Prescription::forOrganization($orgId)->count(),
                'lab_orders' => LabOrder::forOrganization($orgId)->count(),
                'billing_synced' => $synced,
                'billing_sync_rate' => $totalEncounters > 0
                    ? round($synced / $totalEncounters * 100, 1)
                    : 0,
                'audit_events_30d' => AuditLog::query()
                    ->where('organization_id', $orgId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
            ],
        ]);
    }

    public function quality(): JsonResponse
    {
        $orgId = $this->organizationId();

        $hypertensionPatients = PatientProblem::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('icd10_code', 'I10')
                    ->orWhere('description', 'like', '%hypertension%');
            })
            ->pluck('patient_id')
            ->unique();

        $controlled = 0;
        foreach ($hypertensionPatients as $patientId) {
            $latest = PatientVital::query()
                ->where('patient_id', $patientId)
                ->orderByDesc('recorded_at')
                ->first();

            if ($latest && $latest->bp_systolic < 140 && $latest->bp_diastolic < 90) {
                $controlled++;
            }
        }

        $htnTotal = $hypertensionPatients->count();
        $patientsTotal = Patient::forOrganization($orgId)->count();
        $allergyDocumented = PatientAllergyItem::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->distinct('patient_id')
            ->count('patient_id');

        $encounters = Encounter::forOrganization($orgId);
        $signed = (clone $encounters)->whereNotNull('signed_at')->count();
        $encounterTotal = $encounters->count();

        return response()->json([
            'data' => [
                'measures' => [
                    [
                        'id' => 'CMS165',
                        'name' => 'Controlling High Blood Pressure',
                        'numerator' => $controlled,
                        'denominator' => $htnTotal,
                        'rate' => $htnTotal > 0 ? round($controlled / $htnTotal * 100, 1) : 0,
                    ],
                    [
                        'id' => 'DOC-ALLERGY',
                        'name' => 'Allergy documentation coverage',
                        'numerator' => $allergyDocumented,
                        'denominator' => $patientsTotal,
                        'rate' => $patientsTotal > 0 ? round($allergyDocumented / $patientsTotal * 100, 1) : 0,
                    ],
                    [
                        'id' => 'DOC-SIGN',
                        'name' => 'Encounter signature completion',
                        'numerator' => $signed,
                        'denominator' => $encounterTotal,
                        'rate' => $encounterTotal > 0 ? round($signed / $encounterTotal * 100, 1) : 0,
                    ],
                ],
            ],
        ]);
    }

    public function productivity(): JsonResponse
    {
        $orgId = $this->organizationId();

        $providers = Provider::query()
            ->where('organization_id', $orgId)
            ->get();

        $rows = $providers->map(function (Provider $provider) use ($orgId) {
            $encounters = Encounter::query()
                ->where('organization_id', $orgId)
                ->where('provider_id', $provider->id);

            $signed = (clone $encounters)->whereNotNull('signed_at')->count();
            $appointments = Appointment::query()
                ->where('organization_id', $orgId)
                ->where('provider_id', $provider->id)
                ->whereIn('status', ['completed', 'checked_in'])
                ->count();

            return [
                'provider_id' => $provider->id,
                'provider_name' => trim($provider->first_name.' '.$provider->last_name),
                'specialty' => $provider->specialty,
                'encounters_total' => $encounters->count(),
                'encounters_signed' => $signed,
                'appointments_completed' => $appointments,
                'prescriptions_written' => Prescription::query()
                    ->where('organization_id', $orgId)
                    ->where('provider_id', $provider->id)
                    ->count(),
            ];
        })->sortByDesc('encounters_signed')->values();

        return response()->json([
            'data' => [
                'providers' => $rows,
                'summary' => [
                    'total_signed_encounters' => $rows->sum('encounters_signed'),
                    'total_appointments' => $rows->sum('appointments_completed'),
                ],
            ],
        ]);
    }
}
