<?php

namespace App\Http\Controllers\Api;

use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Services\PatientChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientChartController extends ApiController
{
    public function show(Request $request, Patient $patient, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        $filters = $request->only(['document_type', 'document_date', 'uploaded_by']);

        return response()->json(['data' => $chartService->chart($patient, $filters)]);
    }

    public function storeProblem(Request $request, Patient $patient, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        $data = $request->validate([
            'icd10_code' => 'required|string|max:12',
            'description' => 'required|string|max:255',
            'onset_date' => 'nullable|date',
            'status' => 'nullable|string|max:20',
            'rank' => 'nullable|integer|min:1',
        ]);

        $problem = $chartService->addProblem($patient, $data);

        return response()->json(['data' => $problem], 201);
    }

    public function storeVital(Request $request, Patient $patient, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        $data = $request->validate([
            'bp_systolic' => 'nullable|integer|min:40|max:300',
            'bp_diastolic' => 'nullable|integer|min:20|max:200',
            'heart_rate' => 'nullable|integer|min:20|max:250',
            'respiratory_rate' => 'nullable|integer|min:4|max:80',
            'temperature_f' => 'nullable|numeric|min:90|max:110',
            'weight_lb' => 'nullable|numeric|min:1|max:1500',
            'height_in' => 'nullable|numeric|min:10|max:120',
            'spo2' => 'nullable|integer|min:50|max:100',
            'notes' => 'nullable|string|max:500',
            'encounter_id' => 'nullable|exists:encounters,id',
        ]);

        $vital = $chartService->addVital($patient, $data);

        return response()->json(['data' => $vital], 201);
    }

    public function storeDocument(Request $request, Patient $patient, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:64',
            'file_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $document = $chartService->addDocument($patient, $data, auth()->id());

        return response()->json(['data' => $document], 201);
    }

    public function storeAllergy(Request $request, Patient $patient, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        $data = $request->validate([
            'allergen' => 'required|string|max:255',
            'reaction' => 'nullable|string|max:255',
            'severity' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
        ]);

        $allergy = $chartService->addAllergy($patient, $data);

        return response()->json(['data' => $allergy], 201);
    }

    public function checkEligibility(Patient $patient, PatientInsurance $insurance, PatientChartService $chartService): JsonResponse
    {
        $this->ensurePatient($patient);
        if ((int) $insurance->patient_id !== (int) $patient->id) {
            abort(404);
        }

        $updated = $chartService->verifyEligibility($insurance);

        return response()->json([
            'message' => 'Eligibility verified.',
            'data' => $updated,
        ]);
    }

    private function ensurePatient(Patient $patient): void
    {
        if (! $this->belongsToOrganization($patient)) {
            abort(404);
        }
    }
}
