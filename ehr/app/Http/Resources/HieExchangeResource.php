<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HieExchangeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'hie_connection_id' => $this->hie_connection_id,
            'hie_connection' => new HieConnectionResource($this->whenLoaded('hieConnection')),
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'direction' => $this->direction,
            'resource_type' => $this->resource_type,
            'fhir_resource_id' => $this->fhir_resource_id,
            'status' => $this->status,
            'response_message' => $this->response_message,
            'exchanged_at' => $this->exchanged_at?->toIso8601String(),
        ];
    }
}
