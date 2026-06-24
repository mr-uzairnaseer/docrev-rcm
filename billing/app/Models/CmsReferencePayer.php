<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsReferencePayer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'program',
        'ownership',
        'cms_state_id',
        'cms_mac_id',
        'electronic_payer_id',
        'cms_plan_id',
        'plan_type',
        'phone',
        'website',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(CmsState::class, 'cms_state_id');
    }

    public function mac(): BelongsTo
    {
        return $this->belongsTo(CmsMac::class, 'cms_mac_id');
    }
}
