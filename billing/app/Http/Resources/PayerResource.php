<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'payer_type' => $this->payer_type,
            'electronic_payer_id' => $this->electronic_payer_id,
            'cms_reference_payer_id' => $this->cms_reference_payer_id,
            'cms_state_id' => $this->cms_state_id,
            'is_active' => $this->is_active,
        ];
    }
}
