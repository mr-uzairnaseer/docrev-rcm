<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'npi' => $this->npi,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'credentials' => $this->credentials,
            'specialty' => $this->specialty,
            'taxonomy_code' => $this->taxonomy_code,
            'is_active' => $this->is_active,
        ];
    }
}
