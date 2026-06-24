<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientStatement extends Model
{
    use Auditable, HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'patient_account_id',
        'external_claim_uuid',
        'statement_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'balance_due',
        'status',
        'line_items',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'line_items' => 'array',
    ];

    public function patientAccount(): BelongsTo
    {
        return $this->belongsTo(PatientAccount::class);
    }
}
