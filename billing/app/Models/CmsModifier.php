<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsModifier extends Model
{
    protected $fillable = [
        'code',
        'description',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
