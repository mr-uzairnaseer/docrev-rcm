<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPlaceOfServiceCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'definition',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];
}
