<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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
            'pharmacy_id' => $this->pharmacy_id,
            'pharmacy' => new PharmacyResource($this->whenLoaded('pharmacy')),
            'drug_name' => $this->drug_name,
            'ndc' => $this->ndc,
            'strength' => $this->strength,
            'quantity' => $this->quantity,
            'days_supply' => $this->days_supply,
            'refills' => $this->refills,
            'sig' => $this->sig,
            'status' => $this->status,
            'surescripts_message_id' => $this->surescripts_message_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
