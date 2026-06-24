<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EncounterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'patient_id' => $this->patient_id,
            'provider_id' => $this->provider_id,
            'location_id' => $this->location_id,
            'encounter_date' => $this->encounter_date?->toIso8601String(),
            'encounter_type' => $this->encounter_type,
            'status' => $this->status,
            'chief_complaint' => $this->chief_complaint,
            'clinical_notes' => $this->clinical_notes,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'billing_sync_status' => $this->billing_sync_status,
            'billing_synced_at' => $this->billing_synced_at?->toIso8601String(),
            'diagnoses' => $this->whenLoaded('diagnoses'),
            'charges' => $this->whenLoaded('charges'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
