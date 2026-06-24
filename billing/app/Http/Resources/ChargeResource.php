<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'encounter_external_id' => $this->encounter_external_id,
            'service_date' => $this->service_date?->format('Y-m-d'),
            'cpt_code' => $this->cpt_code,
            'hcpcs_code' => $this->hcpcs_code,
            'modifier_1' => $this->modifier_1,
            'modifier_2' => $this->modifier_2,
            'units' => $this->units,
            'charge_amount' => $this->charge_amount,
            'diagnosis_pointers' => $this->diagnosis_pointers,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
