<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacy extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'ncpdp_id',
        'npi',
        'phone',
        'fax',
        'address_line1',
        'city',
        'state',
        'postal_code',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
