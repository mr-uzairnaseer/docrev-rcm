<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payer extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'cms_reference_payer_id',
        'cms_state_id',
        'name',
        'payer_id',
        'payer_type',
        'electronic_payer_id',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function cmsReferencePayer(): BelongsTo
    {
        return $this->belongsTo(CmsReferencePayer::class);
    }

    public function cmsState(): BelongsTo
    {
        return $this->belongsTo(CmsState::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }
}
