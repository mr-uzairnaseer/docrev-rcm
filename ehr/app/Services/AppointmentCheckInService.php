<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Encounter;

class AppointmentCheckInService
{
    public function __construct(
        private PortalAppointmentSyncService $portalSync
    ) {}

    public function checkIn(Appointment $appointment): Appointment
    {
        if ($appointment->status === Appointment::STATUS_CANCELLED) {
            abort(422, 'Cannot check in a cancelled appointment.');
        }

        if ($appointment->encounter_id) {
            if ($appointment->status !== Appointment::STATUS_CHECKED_IN) {
                $appointment->update(['status' => Appointment::STATUS_CHECKED_IN]);
            }

            $this->syncPortal($appointment);

            return $appointment->fresh()->load(['patient', 'provider', 'location', 'encounter']);
        }

        if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
            abort(422, 'Only scheduled appointments can be checked in.');
        }

        $encounter = Encounter::create([
            'organization_id' => $appointment->organization_id,
            'patient_id' => $appointment->patient_id,
            'provider_id' => $appointment->provider_id,
            'location_id' => $appointment->location_id,
            'encounter_date' => now(),
            'encounter_type' => $appointment->appointment_type ?? 'office_visit',
            'status' => Encounter::STATUS_IN_PROGRESS,
            'chief_complaint' => $appointment->notes,
        ]);

        $appointment->update([
            'status' => Appointment::STATUS_CHECKED_IN,
            'encounter_id' => $encounter->id,
        ]);

        $this->syncPortal($appointment->fresh()->load(['patient', 'provider', 'location', 'organization']));

        return $appointment->fresh()->load(['patient', 'provider', 'location', 'encounter']);
    }

    private function syncPortal(Appointment $appointment): void
    {
        $appointment->loadMissing(['patient', 'provider', 'location', 'organization']);
        $this->portalSync->sync($appointment);
    }
}
