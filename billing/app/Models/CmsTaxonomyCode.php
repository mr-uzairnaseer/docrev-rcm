<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsTaxonomyCode extends Model
{
    protected $fillable = [
        'code',
        'grouping',
        'classification',
        'specialization',
        'definition',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
