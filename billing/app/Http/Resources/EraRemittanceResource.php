<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EraRemittanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'trace_number' => $this->trace_number,
            'total_payment_amount' => $this->total_payment_amount,
            'claim_count' => $this->claim_count,
            'matched_count' => $this->matched_count,
            'status' => $this->status,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'claim_payments' => $this->whenLoaded('claimPayments', function () {
                return $this->claimPayments->map(fn ($p) => [
                    'id' => $p->id,
                    'claim_number' => $p->claim_number,
                    'claim_id' => $p->claim_id,
                    'paid_amount' => $p->paid_amount,
                    'patient_responsibility' => $p->patient_responsibility,
                    'payment_status' => $p->payment_status,
                    'portal_synced' => $p->portal_synced,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
