<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Claim extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';
    public const STATUS_DENIED = 'denied';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'organization_id',
        'uuid',
        'patient_id',
        'payer_id',
        'original_claim_id',
        'claim_number',
        'claim_type',
        'frequency_code',
        'service_date_from',
        'service_date_to',
        'total_charge_amount',
        'paid_amount',
        'patient_responsibility',
        'status',
        'submitted_at',
        'paid_at',
        'icd10_codes',
        'rendering_provider_npi',
        'billing_provider_npi',
        'place_of_service',
        'edi_837_content',
        'edi_generated_at',
        'scrub_errors',
    ];

    protected $casts = [
        'service_date_from' => 'date',
        'service_date_to' => 'date',
        'total_charge_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'edi_generated_at' => 'datetime',
        'icd10_codes' => 'array',
        'scrub_errors' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Claim $claim) {
            if (empty($claim->uuid)) {
                $claim->uuid = (string) Str::uuid();
            }

            if (empty($claim->status)) {
                $claim->status = self::STATUS_DRAFT;
            }

            if (empty($claim->frequency_code)) {
                $claim->frequency_code = '1';
            }
        });
    }

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

    public function claimLines(): HasMany
    {
        return $this->hasMany(ClaimLine::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ClaimSubmission::class);
    }

    public function claimPayments(): HasMany
    {
        return $this->hasMany(ClaimPayment::class);
    }

    public function denials(): HasMany
    {
        return $this->hasMany(ClaimDenial::class);
    }

    public function originalClaim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'original_claim_id');
    }

    public function correctedClaims(): HasMany
    {
        return $this->hasMany(Claim::class, 'original_claim_id');
    }
}
