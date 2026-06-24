<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurescriptsEnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'spi' => $this->spi,
            'dea_number' => $this->dea_number,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
