<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimLine extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'claim_id',
        'charge_id',
        'line_number',
        'cpt_code',
        'modifier_1',
        'modifier_2',
        'units',
        'charge_amount',
        'paid_amount',
        'adjustment_amount',
        'patient_responsibility',
        'diagnosis_pointers',
    ];

    protected $casts = [
        'charge_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'units' => 'integer',
        'diagnosis_pointers' => 'array',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
