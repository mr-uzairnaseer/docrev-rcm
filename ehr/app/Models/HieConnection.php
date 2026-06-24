<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HieConnection extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'organization_id',
        'name',
        'network_type',
        'fhir_base_url',
        'client_id',
        'client_secret',
        'scopes',
        'status',
        'agreement_signed_at',
        'agreement_notes',
    ];

    protected $casts = ['agreement_signed_at' => 'datetime'];

    protected $hidden = ['client_secret'];

    public function exchanges(): HasMany
    {
        return $this->hasMany(HieExchange::class);
    }
}
