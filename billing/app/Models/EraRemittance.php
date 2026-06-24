<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EraRemittance extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'trace_number',
        'edi_835_content',
        'total_payment_amount',
        'claim_count',
        'matched_count',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'total_payment_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function claimPayments(): HasMany
    {
        return $this->hasMany(ClaimPayment::class);
    }
}
