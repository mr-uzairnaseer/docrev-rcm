<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInsurance extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'payer_name',
        'member_id',
        'group_number',
        'plan_type',
        'coverage_status',
        'copay_amount',
        'last_verified_at',
    ];

    protected $casts = [
        'copay_amount' => 'decimal:2',
        'last_verified_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
