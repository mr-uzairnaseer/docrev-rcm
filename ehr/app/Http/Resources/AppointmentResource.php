<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'provider_id' => $this->provider_id,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'encounter_id' => $this->encounter_id,
            'encounter' => new EncounterResource($this->whenLoaded('encounter')),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'appointment_type' => $this->appointment_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'portal_sync_status' => $this->portal_sync_status,
            'portal_synced_at' => $this->portal_synced_at?->toIso8601String(),
        ];
    }
}
