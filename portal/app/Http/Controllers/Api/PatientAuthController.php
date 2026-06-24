<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\PatientLoginRequest;
use App\Http\Resources\PatientAccountResource;
use App\Http\Resources\PatientStatementResource;
use App\Http\Resources\PortalAppointmentResource;
use App\Models\PatientAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class PatientAuthController extends ApiController
{
    public function login(PatientLoginRequest $request): JsonResponse
    {
        $account = PatientAccount::where('email', $request->email)->first();

        if (! $account || ! Hash::check($request->password, $account->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $account->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        $account->update(['last_login_at' => now()]);
        $token = $account->createToken('patient-portal')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'patient' => new PatientAccountResource($account),
        ]);
    }

    public function me(Request $request): PatientAccountResource
    {
        return new PatientAccountResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function appointments(Request $request): AnonymousResourceCollection
    {
        $appointments = $request->user()
            ->appointments()
            ->orderBy('appointment_at')
            ->paginate($request->integer('per_page', 15));

        return PortalAppointmentResource::collection($appointments);
    }

    public function statements(Request $request): AnonymousResourceCollection
    {
        $statements = $request->user()
            ->statements()
            ->orderByDesc('statement_date')
            ->paginate($request->integer('per_page', 15));

        return PatientStatementResource::collection($statements);
    }

    public function medications(Request $request): JsonResponse
    {
        $patient = $request->user();
        $ehrUrl = config('services.ehr.url');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.ehr.api_key'),
                    'Accept' => 'application/json',
                ])
                ->get(rtrim($ehrUrl, '/')."/api/internal/patients/{$patient->ehr_patient_uuid}/prescriptions");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Unable to fetch medications from clinical systems.'], $response->status());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error communicating with clinical systems: ' . $e->getMessage()], 500);
        }
    }

    public function requestAppointment(Request $request): JsonResponse
    {
        $patient = $request->user();
        
        $request->validate([
            'provider_id' => ['required', 'integer'],
            'provider_name' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // 1. Create a draft portal appointment locally with status requested
        $portalAppt = \App\Models\PortalAppointment::create([
            'patient_account_id' => $patient->id,
            'provider_name' => $request->provider_name,
            'location_name' => 'Main Office',
            'appointment_at' => $request->scheduled_at,
            'appointment_type' => 'office_visit',
            'status' => 'requested',
            'notes' => $request->notes,
        ]);

        // 2. Send the request to EHR internal API
        $ehrUrl = config('services.ehr.url');
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.ehr.api_key'),
                    'Accept' => 'application/json',
                ])
                ->post(rtrim($ehrUrl, '/').'/api/internal/appointments/request', [
                    'patient_uuid' => $patient->ehr_patient_uuid,
                    'provider_id' => $request->provider_id,
                    'scheduled_at' => $request->scheduled_at,
                    'notes' => $request->notes,
                    'portal_appointment_id' => $portalAppt->id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $portalAppt->update([
                    'external_appointment_id' => $data['appointment_uuid'] ?? null
                ]);
                return response()->json([
                    'success' => true,
                    'appointment' => $portalAppt,
                ], 201);
            }

            $portalAppt->delete();
            return response()->json(['message' => 'Could not sync appointment request with clinical systems.'], $response->status());
        } catch (\Throwable $e) {
            $portalAppt->delete();
            return response()->json(['message' => 'Error communicating with clinical systems: ' . $e->getMessage()], 500);
        }
    }

    public function providers(Request $request): JsonResponse
    {
        $ehrUrl = config('services.ehr.url');
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'X-DocRev-Api-Key' => config('services.ehr.api_key'),
                    'Accept' => 'application/json',
                ])
                ->get(rtrim($ehrUrl, '/').'/api/internal/providers');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Unable to fetch providers.'], $response->status());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error communicating with clinical systems: ' . $e->getMessage()], 500);
        }
    }
}
