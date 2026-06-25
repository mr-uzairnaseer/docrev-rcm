<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EftEnrollment extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'legal_name',
        'dba',
        'npi',
        'tax_id',
        'ptan',
        'address',
        'contact_name',
        'contact_phone',
        'contact_email',
        'bank_routing',
        'bank_account',
        'bank_account_type',
        'authorized_signer',
        'medicare_eft_status',
        'commercial_payer_status',
        'era_enrollment_status',
        'vcc_policy',
        'onboarding_checklist',
    ];

    protected $casts = [
        'address' => 'json',
        'commercial_payer_status' => 'json',
        'era_enrollment_status' => 'json',
        'onboarding_checklist' => 'json',
        'bank_routing' => 'encrypted',
        'bank_account' => 'encrypted',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
