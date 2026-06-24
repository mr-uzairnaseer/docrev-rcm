<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabVendor extends Model
{
    use BelongsToOrganization, HasFactory;

    public const TYPE_HL7_V2 = 'hl7_v2';
    public const TYPE_FHIR = 'fhir';

    protected $fillable = [
        'organization_id',
        'name',
        'interface_type',
        'vendor_code',
        'host',
        'port',
        'sending_application',
        'receiving_application',
        'sending_facility',
        'receiving_facility',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }
}
