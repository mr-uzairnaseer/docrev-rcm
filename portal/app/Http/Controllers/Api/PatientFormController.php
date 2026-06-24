<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalPatientForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientFormController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user();
        $forms = PortalPatientForm::where('patient_account_id', $patient->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'forms' => $forms
        ]);
    }

    public function sign(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'signature_name' => ['required', 'string', 'max:255'],
        ]);

        $patient = $request->user();
        $form = PortalPatientForm::where('external_form_uuid', $uuid)
            ->where('patient_account_id', $patient->id)
            ->first();

        if (!$form) {
            return response()->json(['message' => 'Form not found.'], 404);
        }

        if ($form->status === 'signed') {
            return response()->json(['message' => 'Form is already signed.'], 422);
        }

        // Call EHR internal API to record signature
        $ehrUrl = config('services.ehr.url');
        if ($ehrUrl) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'X-DocRev-Api-Key' => config('services.ehr.api_key'),
                        'Accept' => 'application/json',
                    ])
                    ->post(rtrim($ehrUrl, '/')."/api/internal/forms/{$uuid}/sign", [
                        'signature_name' => $request->signature_name,
                    ]);

                if (!$response->successful()) {
                    return response()->json(['message' => 'Could not sync signature with clinical systems.'], 500);
                }
            } catch (\Throwable $e) {
                Log::error('Error syncing signature to EHR: ' . $e->getMessage());
                return response()->json(['message' => 'Unable to sync signature with clinical systems.'], 500);
            }
        }

        // Update locally
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
