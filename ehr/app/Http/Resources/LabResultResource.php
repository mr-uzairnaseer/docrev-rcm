<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LabResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'result_code' => $this->result_code,
            'result_name' => $this->result_name,
            'value' => $this->value,
            'unit' => $this->unit,
            'reference_range' => $this->reference_range,
            'abnormal_flag' => $this->abnormal_flag,
            'status' => $this->status,
            'observed_at' => $this->observed_at?->toIso8601String(),
        ];
    }
}
