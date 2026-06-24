<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'cpt_code',
        'hcpcs_code',
        'modifier_1',
        'modifier_2',
        'units',
        'charge_amount',
        'diagnosis_pointers',
        'synced_to_billing',
        'synced_at',
    ];

    protected $casts = [
        'charge_amount' => 'decimal:2',
        'units' => 'integer',
        'diagnosis_pointers' => 'array',
        'synced_to_billing' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
