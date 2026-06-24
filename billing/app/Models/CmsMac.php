<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMac extends Model
{
    protected $fillable = [
        'contract_number',
        'name',
        'mac_type',
        'jurisdiction_code',
        'website',
        'phone',
        'address',
        'processes_hhh',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'processes_hhh' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function states(): BelongsToMany
    {
        return $this->belongsToMany(CmsState::class, 'cms_mac_states')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function payers(): HasMany
    {
        return $this->hasMany(CmsReferencePayer::class);
    }
}
