<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ehr_patient_uuid' => $this->ehr_patient_uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'mrn' => $this->mrn,
            'insurance_member_id' => $this->insurance_member_id,
            'insurance_group_number' => $this->insurance_group_number,
            'is_active' => $this->is_active,
        ];
    }
}
