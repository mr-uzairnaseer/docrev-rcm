<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PatientStatementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'statement_date' => $this->statement_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_due' => $this->balance_due,
            'status' => $this->status,
            'line_items' => $this->line_items,
        ];
    }
}
