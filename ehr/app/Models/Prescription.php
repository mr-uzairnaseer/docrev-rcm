<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Prescription extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'uuid',
        'patient_id',
        'provider_id',
        'encounter_id',
        'pharmacy_id',
        'drug_name',
        'ndc',
        'strength',
        'dosage_form',
        'quantity',
        'days_supply',
        'refills',
        'sig',
        'status',
        'surescripts_message_id',
        'transmission_payload',
        'transmission_response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prescription $rx) {
            if (empty($rx->uuid)) {
                $rx->uuid = (string) Str::uuid();
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
