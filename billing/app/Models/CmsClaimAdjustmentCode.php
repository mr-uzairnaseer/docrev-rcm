<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsClaimAdjustmentCode extends Model
{
    protected $fillable = [
        'code',
        'group_code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
