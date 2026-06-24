<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsMedicareAdvantageContract extends Model
{
    protected $fillable = [
        'contract_number',
        'organization_type',
        'plan_type',
        'organization_name',
        'marketing_name',
        'parent_organization',
        'contract_effective_date',
        'offers_part_d',
        'ma_enrollment',
        'part_d_enrollment',
        'total_enrollment',
        'ownership',
        'is_active',
    ];

    protected $casts = [
        'contract_effective_date' => 'date',
        'offers_part_d' => 'boolean',
        'is_active' => 'boolean',
    ];
}
