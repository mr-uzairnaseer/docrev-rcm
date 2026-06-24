<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Encounter extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'uuid',
        'patient_id',
        'provider_id',
        'location_id',
        'encounter_date',
        'encounter_type',
        'status',
        'chief_complaint',
        'clinical_notes',
        'signed_at',
        'signed_by',
        'billing_synced_at',
        'billing_sync_status',
    ];

    protected $casts = [
        'encounter_date' => 'datetime',
        'signed_at' => 'datetime',
        'billing_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Encounter $encounter) {
            if (empty($encounter->uuid)) {
                $encounter->uuid = (string) Str::uuid();
            }

            if (empty($encounter->status)) {
                $encounter->status = self::STATUS_SCHEDULED;
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(EncounterDiagnosis::class)->orderBy('sequence');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(EncounterCharge::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }
}
