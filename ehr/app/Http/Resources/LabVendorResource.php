<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LabVendorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'interface_type' => $this->interface_type,
            'vendor_code' => $this->vendor_code,
        ];
    }
}
