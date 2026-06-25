<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EftDeposit extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payer_id',
        'trace_number',
        'amount',
        'deposit_date',
        'matched_status',
        'era_remittance_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposit_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Payer::class);
    }

    public function eraRemittance(): BelongsTo
    {
        return $this->belongsTo(EraRemittance::class);
    }
}
