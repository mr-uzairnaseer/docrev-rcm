<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HieConnectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'network_type' => $this->network_type,
            'fhir_base_url' => $this->fhir_base_url,
            'status' => $this->status,
            'agreement_signed_at' => $this->agreement_signed_at?->toIso8601String(),
            'agreement_notes' => $this->agreement_notes,
        ];
    }
}
