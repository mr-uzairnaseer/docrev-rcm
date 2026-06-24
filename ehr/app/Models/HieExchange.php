<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HieExchange extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'hie_connection_id',
        'patient_id',
        'direction',
        'resource_type',
        'fhir_resource_id',
        'payload',
        'status',
        'response_message',
        'exchanged_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'exchanged_at' => 'datetime',
    ];

    public function hieConnection(): BelongsTo
    {
        return $this->belongsTo(HieConnection::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
