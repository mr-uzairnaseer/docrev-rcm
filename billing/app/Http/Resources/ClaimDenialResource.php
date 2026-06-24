<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimDenialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'claim_id' => $this->claim_id,
            'claim' => new ClaimResource($this->whenLoaded('claim')),
            'reason_code' => $this->reason_code,
            'reason_description' => $this->reason_description,
            'denied_amount' => $this->denied_amount,
            'status' => $this->status,
            'appeal_notes' => $this->appeal_notes,
            'appealed_at' => $this->appealed_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
