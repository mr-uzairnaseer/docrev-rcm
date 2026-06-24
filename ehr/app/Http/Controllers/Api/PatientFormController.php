<?php

namespace App\Http\Controllers\Api;

use App\Models\Patient;
use App\Models\PatientForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientFormController extends ApiController
{
    public function index(Patient $patient): JsonResponse
    {
        $this->ensureBelongsToOrganization($patient);

        $forms = PatientForm::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'forms' => $forms
        ]);
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        $this->ensureBelongsToOrganization($patient);

        $request->validate([
            'form_name' => ['required', 'string', 'max:255'],
            'form_content' => ['nullable', 'string'],
        ]);

        $defaultContent = match ($request->form_name) {
            'HIPAA Privacy Consent' => "I hereby authorize DocRev Clinical Group to use and disclose my protected health information for treatment, payment, and healthcare operations. I understand my rights under HIPAA to inspect and request amendments to my clinical records.",
            'COVID-19 Health Screening' => "Have you experienced any fever, cough, shortness of breath, or loss of taste/smell in the past 14 days? Have you been in contact with anyone diagnosed with COVID-19? If yes, please details below.",
            'Patient Intake & History' => "Please verify your personal details. Do you have any family history of heart disease, diabetes, or hypertension? Please list any active medications, surgeries, or chronic conditions.",
            default => $request->form_content ?? "Consent Form Content"
        };

        $form = PatientForm::create([
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
            'form_name' => $request->form_name,
            'status' => 'pending',
            'form_content' => $defaultContent,
        ]);

        // Sync to Portal
        $portalUrl = config('services.portal.url');
        if ($portalUrl) {
            try {
                Http::timeout(10)
                    ->withHeaders([
                        'X-DocRev-Api-Key' => config('services.portal.api_key'),
                        'Accept' => 'application/json',
                    ])
                    ->post(rtrim($portalUrl, '/').'/api/internal/form-sync', [
                        'ehr_patient_uuid' => $patient->uuid,
                        'form' => [
                            'uuid' => $form->uuid,
                            'form_name' => $form->form_name,
                            'status' => $form->status,
                            'form_content' => $form->form_content,
                        ],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to sync form to patient portal: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'form' => $form
        ], 201);
    }

    private function ensureBelongsToOrganization(Patient $patient): void
    {
        if ($patient->organization_id !== $this->organizationId()) {
            abort(404);
        }
    }
}
