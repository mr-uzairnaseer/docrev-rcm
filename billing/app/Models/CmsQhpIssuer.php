<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsQhpIssuer extends Model
{
    protected $fillable = [
        'issuer_id',
        'issuer_name',
        'cms_state_id',
        'market_type',
        'ownership',
        'website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(CmsState::class, 'cms_state_id');
    }
}
