<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimDenial extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_APPEALED = 'appealed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'organization_id',
        'claim_id',
        'claim_payment_id',
        'reason_code',
        'reason_description',
        'denied_amount',
        'status',
        'appeal_notes',
        'appealed_at',
        'resolved_at',
    ];

    protected $casts = [
        'denied_amount' => 'decimal:2',
        'appealed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function claimPayment(): BelongsTo
    {
        return $this->belongsTo(ClaimPayment::class);
    }
}
