<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'organization_id',
        'uuid',
        'patient_id',
        'provider_id',
        'location_id',
        'encounter_id',
        'scheduled_at',
        'duration_minutes',
        'appointment_type',
        'status',
        'notes',
        'portal_sync_status',
        'portal_synced_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'portal_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            if (empty($appointment->uuid)) {
                $appointment->uuid = (string) Str::uuid();
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
