<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Api\ApiController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentSyncController extends ApiController
{
    public function requestAppointment(Request $request): JsonResponse
    {
        $request->validate([
            'patient_uuid' => ['required', 'uuid'],
            'provider_id' => ['required', 'exists:providers,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'appointment_type' => ['nullable', 'string'],
            'portal_appointment_id' => ['required'],
        ]);

        $patient = Patient::where('uuid', $request->patient_uuid)->first();
        if (!$patient) {
            return response()->json(['message' => 'Patient not found.'], 404);
        }

        // Auto-assign location if null
        $locationId = $request->location_id;
        if (!$locationId) {
            $locationId = Location::where('organization_id', $patient->organization_id)->first()?->id;
        }

        $appointment = Appointment::create([
            'organization_id' => $patient->organization_id,
            'patient_id' => $patient->id,
            'provider_id' => $request->provider_id,
            'location_id' => $locationId,
            'scheduled_at' => $request->scheduled_at,
            'duration_minutes' => 30,
            'appointment_type' => $request->appointment_type ?? 'office_visit',
            'status' => 'requested',
            'notes' => $request->notes,
            'portal_sync_status' => 'synced',
            'portal_synced_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'appointment_uuid' => $appointment->uuid,
            'appointment_id' => $appointment->id,
        ], 201);
    }

    public function providers(): JsonResponse
    {
        $providers = Provider::all();
        return response()->json([
            'providers' => $providers->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => "{$p->first_name} {$p->last_name}",
                    'specialty' => $p->specialty,
                ];
            })
        ]);
    }
}
