<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Api\ApiController;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;

class PatientDataController extends ApiController
{
    public function prescriptions($uuid): JsonResponse
    {
        $patient = Patient::where('uuid', $uuid)->first();
        
        if (!$patient) {
            return response()->json(['message' => 'Patient not found.'], 404);
        }

        $prescriptions = Prescription::where('patient_id', $patient->id)
            ->with(['provider', 'pharmacy'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'prescriptions' => $prescriptions->map(function ($rx) {
                return [
                    'id' => $rx->id,
                    'drug_name' => $rx->drug_name,
                    'ndc' => $rx->ndc,
                    'strength' => $rx->strength,
                    'dosage_form' => $rx->dosage_form,
                    'quantity' => $rx->quantity,
                    'days_supply' => $rx->days_supply,
                    'refills' => $rx->refills,
                    'sig' => $rx->sig,
                    'status' => $rx->status,
                    'provider_name' => $rx->provider ? "{$rx->provider->first_name} {$rx->provider->last_name}" : '—',
                    'pharmacy_name' => $rx->pharmacy ? $rx->pharmacy->name : '—',
                    'sent_at' => $rx->sent_at?->toIso8601String(),
                ];
            })
        ]);
    }
}
