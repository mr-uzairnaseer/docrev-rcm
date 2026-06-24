<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class PatientAccount extends Authenticatable
{
    use Auditable, BelongsToOrganization, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'uuid',
        'ehr_patient_uuid',
        'billing_patient_uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'password',
        'email_verified_at',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientAccount $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(PortalAppointment::class);
    }

    public function statements(): HasMany
    {
        return $this->hasMany(PatientStatement::class);
    }
}
