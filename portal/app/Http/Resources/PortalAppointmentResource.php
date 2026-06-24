<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PortalAppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider_name' => $this->provider_name,
            'location_name' => $this->location_name,
            'appointment_at' => $this->appointment_at?->toIso8601String(),
            'appointment_type' => $this->appointment_type,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
