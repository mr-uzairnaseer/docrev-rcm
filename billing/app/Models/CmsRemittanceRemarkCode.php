<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsRemittanceRemarkCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
