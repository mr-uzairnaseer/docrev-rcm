<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsIcd10Code extends Model
{
    protected $fillable = [
        'code',
        'description',
        'is_billable',
        'is_active',
    ];

    protected $casts = [
        'is_billable' => 'boolean',
        'is_active' => 'boolean',
    ];
}
