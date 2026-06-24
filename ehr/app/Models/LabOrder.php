<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LabOrder extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    public const STATUS_ORDERED = 'ordered';
    public const STATUS_SENT = 'sent';
    public const STATUS_RESULTED = 'resulted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'uuid',
        'patient_id',
        'provider_id',
        'encounter_id',
        'lab_vendor_id',
        'test_code',
        'test_name',
        'priority',
        'status',
        'hl7_orm_message',
        'external_order_id',
        'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (LabOrder $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
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

    public function labVendor(): BelongsTo
    {
        return $this->belongsTo(LabVendor::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }
}
