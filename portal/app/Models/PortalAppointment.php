<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAppointment extends Model
{
    use Auditable, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'patient_account_id',
        'external_appointment_id',
        'provider_name',
        'location_name',
        'appointment_at',
        'appointment_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_at' => 'datetime',
    ];

    public function patientAccount(): BelongsTo
    {
        return $this->belongsTo(PatientAccount::class);
    }
}
