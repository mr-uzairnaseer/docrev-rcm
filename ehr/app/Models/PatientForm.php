<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PatientForm extends Model
{
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'uuid',
        'form_name',
        'status',
        'form_content',
        'signature_name',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientForm $form) {
            if (empty($form->uuid)) {
                $form->uuid = (string) Str::uuid();
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
