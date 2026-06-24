<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsState extends Model
{
    protected $fillable = [
        'code',
        'name',
        'cms_region_id',
        'jurisdiction_type',
        'fips_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(CmsRegion::class, 'cms_region_id');
    }

    public function macs(): BelongsToMany
    {
        return $this->belongsToMany(CmsMac::class, 'cms_mac_states')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function payers(): HasMany
    {
        return $this->hasMany(CmsReferencePayer::class);
    }
}
