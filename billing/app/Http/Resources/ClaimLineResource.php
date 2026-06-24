<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'cpt_code' => $this->cpt_code,
            'modifier_1' => $this->modifier_1,
            'modifier_2' => $this->modifier_2,
            'units' => $this->units,
            'charge_amount' => $this->charge_amount,
            'paid_amount' => $this->paid_amount,
            'adjustment_amount' => $this->adjustment_amount,
            'patient_responsibility' => $this->patient_responsibility,
            'diagnosis_pointers' => $this->diagnosis_pointers,
        ];
    }
}
