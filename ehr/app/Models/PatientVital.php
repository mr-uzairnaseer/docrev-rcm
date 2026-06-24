<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientVital extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'encounter_id',
        'recorded_at',
        'bp_systolic',
        'bp_diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature_f',
        'weight_lb',
        'height_in',
        'spo2',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature_f' => 'decimal:1',
        'weight_lb' => 'decimal:1',
        'height_in' => 'decimal:1',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
