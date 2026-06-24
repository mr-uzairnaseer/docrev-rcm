<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimPayment extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_DENIED = 'denied';

    protected $fillable = [
        'era_remittance_id',
        'claim_id',
        'claim_number',
        'era_status',
        'total_charge',
        'paid_amount',
        'patient_responsibility',
        'payment_status',
        'portal_synced',
    ];

    protected $casts = [
        'total_charge' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'portal_synced' => 'boolean',
    ];

    public function eraRemittance(): BelongsTo
    {
        return $this->belongsTo(EraRemittance::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
