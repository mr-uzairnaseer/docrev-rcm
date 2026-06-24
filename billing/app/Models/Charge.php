<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Charge extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready';
    public const STATUS_BILLED = 'billed';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'organization_id',
        'patient_id',
        'encounter_external_id',
        'service_date',
        'cpt_code',
        'hcpcs_code',
        'modifier_1',
        'modifier_2',
        'units',
        'charge_amount',
        'diagnosis_pointers',
        'icd10_codes',
        'status',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'charge_amount' => 'decimal:2',
        'units' => 'integer',
        'diagnosis_pointers' => 'array',
        'icd10_codes' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function claimLines(): HasMany
    {
        return $this->hasMany(ClaimLine::class);
    }
}
