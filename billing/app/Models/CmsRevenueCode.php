<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsRevenueCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
