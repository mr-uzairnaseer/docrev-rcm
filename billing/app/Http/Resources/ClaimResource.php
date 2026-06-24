<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'claim_number' => $this->claim_number,
            'claim_type' => $this->claim_type,
            'frequency_code' => $this->frequency_code,
            'original_claim_id' => $this->original_claim_id,
            'original_claim_number' => $this->whenLoaded('originalClaim', fn () => $this->originalClaim?->claim_number),
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'payer_id' => $this->payer_id,
            'service_date_from' => $this->service_date_from?->format('Y-m-d'),
            'service_date_to' => $this->service_date_to?->format('Y-m-d'),
            'total_charge_amount' => $this->total_charge_amount,
            'paid_amount' => $this->paid_amount,
            'patient_responsibility' => $this->patient_responsibility,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'icd10_codes' => $this->icd10_codes,
            'rendering_provider_npi' => $this->rendering_provider_npi,
            'billing_provider_npi' => $this->billing_provider_npi,
            'place_of_service' => $this->place_of_service,
            'claim_lines' => ClaimLineResource::collection($this->whenLoaded('claimLines')),
            'scrub_errors' => $this->scrub_errors,
            'edi_generated_at' => $this->edi_generated_at?->toIso8601String(),
            'has_edi' => ! empty($this->edi_837_content),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
