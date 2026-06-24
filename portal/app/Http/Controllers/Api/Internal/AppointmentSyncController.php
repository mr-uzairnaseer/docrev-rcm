<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Models\PortalAppointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ehr_patient_uuid' => ['required', 'uuid'],
            'appointment.uuid' => ['required', 'uuid'],
            'appointment.provider_name' => ['required', 'string'],
            'appointment.location_name' => ['nullable', 'string'],
            'appointment.appointment_at' => ['required', 'date'],
            'appointment.appointment_type' => ['nullable', 'string'],
            'appointment.status' => ['required', 'string'],
            'appointment.notes' => ['nullable', 'string'],
        ]);

        $account = PatientAccount::where('ehr_patient_uuid', $validated['ehr_patient_uuid'])->first();
        if (! $account) {
            return response()->json(['message' => 'Portal patient account not found. Register patient in EHR first.'], 404);
        }

        $appt = $validated['appointment'];

        $appointment = PortalAppointment::updateOrCreate(
            ['external_appointment_id' => $appt['uuid']],
            [
                'patient_account_id' => $account->id,
                'provider_name' => $appt['provider_name'],
                'location_name' => $appt['location_name'] ?? null,
                'appointment_at' => $appt['appointment_at'],
                'appointment_type' => $appt['appointment_type'] ?? 'office_visit',
                'status' => $appt['status'],
                'notes' => $appt['notes'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Appointment synced to portal.',
            'appointment_id' => $appointment->id,
        ]);
    }
}
