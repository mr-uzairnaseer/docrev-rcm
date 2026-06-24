<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsTypeOfBillCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'facility_type',
        'care_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
