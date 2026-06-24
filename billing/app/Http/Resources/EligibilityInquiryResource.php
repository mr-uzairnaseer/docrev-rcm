<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EligibilityInquiryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'trace_number' => $this->trace_number,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'payer' => new PayerResource($this->whenLoaded('payer')),
            'patient_id' => $this->patient_id,
            'payer_id' => $this->payer_id,
            'service_date' => $this->service_date?->format('Y-m-d'),
            'member_id' => $this->member_id,
            'coverage_status' => $this->coverage_status,
            'plan_name' => $this->plan_name,
            'copay_amount' => $this->copay_amount,
            'deductible_amount' => $this->deductible_amount,
            'coinsurance_percent' => $this->coinsurance_percent,
            'response_message' => $this->response_message,
            'checked_at' => $this->checked_at?->toIso8601String(),
            'has_edi' => ! empty($this->edi_270_content),
        ];
    }
}
