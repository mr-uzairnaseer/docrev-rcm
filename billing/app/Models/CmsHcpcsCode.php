<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsHcpcsCode extends Model
{
    protected $fillable = [
        'code',
        'short_description',
        'long_description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
