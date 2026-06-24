<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalPatientForm extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'patient_account_id',
        'external_form_uuid',
        'form_name',
        'status',
        'form_content',
        'signature_name',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function patientAccount(): BelongsTo
    {
        return $this->belongsTo(PatientAccount::class);
    }
}
