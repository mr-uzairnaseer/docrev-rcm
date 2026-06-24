<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPayment extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'organization_id',
        'patient_id',
        'claim_id',
        'external_claim_uuid',
        'amount',
        'payment_method',
        'reference_number',
        'status',
        'portal_synced',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'portal_synced' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
