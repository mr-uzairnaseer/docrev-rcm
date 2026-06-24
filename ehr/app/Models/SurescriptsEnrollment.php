<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurescriptsEnrollment extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'provider_id',
        'spi',
        'dea_number',
        'status',
        'enrolled_at',
        'notes',
    ];

    protected $casts = ['enrolled_at' => 'datetime'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
