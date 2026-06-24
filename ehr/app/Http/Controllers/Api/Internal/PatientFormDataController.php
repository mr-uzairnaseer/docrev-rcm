<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Api\ApiController;
use App\Models\Patient;
use App\Models\PatientForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientFormDataController extends ApiController
{
    public function getPatientForms($uuid): JsonResponse
    {
        $patient = Patient::where('uuid', $uuid)->first();
        if (!$patient) {
            return response()->json(['message' => 'Patient not found.'], 404);
        }

        $forms = PatientForm::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'forms' => $forms
        ]);
    }

    public function signForm(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'signature_name' => ['required', 'string', 'max:255'],
        ]);

        $form = PatientForm::where('uuid', $uuid)->first();
        if (!$form) {
            return response()->json(['message' => 'Form not found.'], 404);
        }

        $form->update([
            'status' => 'signed',
            'signature_name' => $request->signature_name,
            'signed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'form' => $form
        ]);
    }
}
