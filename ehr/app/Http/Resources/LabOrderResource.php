<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'provider_id' => $this->provider_id,
            'lab_vendor_id' => $this->lab_vendor_id,
            'lab_vendor' => new LabVendorResource($this->whenLoaded('labVendor')),
            'test_code' => $this->test_code,
            'test_name' => $this->test_name,
            'priority' => $this->priority,
            'status' => $this->status,
            'external_order_id' => $this->external_order_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'results' => LabResultResource::collection($this->whenLoaded('results')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
