<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ncpdp_id' => $this->ncpdp_id,
            'phone' => $this->phone,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
