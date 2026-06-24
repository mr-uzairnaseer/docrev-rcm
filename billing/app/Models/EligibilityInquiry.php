<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityInquiry extends Model
{
    use BelongsToOrganization, HasFactory;

    public const COVERAGE_ACTIVE = 'active';
    public const COVERAGE_INACTIVE = 'inactive';
    public const COVERAGE_UNKNOWN = 'unknown';

    protected $fillable = [
        'organization_id',
        'patient_id',
        'payer_id',
        'trace_number',
        'service_date',
        'member_id',
        'edi_270_content',
        'edi_271_content',
        'coverage_status',
        'plan_name',
        'copay_amount',
        'deductible_amount',
        'coinsurance_percent',
        'response_message',
        'checked_at',
    ];

    protected $casts = [
        'service_date' => 'date',
        'copay_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'coinsurance_percent' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }
}
