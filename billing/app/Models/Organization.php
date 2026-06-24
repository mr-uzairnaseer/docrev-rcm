<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'npi',
        'tax_id',
        'phone',
        'email',
        'address',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (empty($organization->uuid)) {
                $organization->uuid = (string) Str::uuid();
            }

            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(OrganizationProvider::class);
    }

    public function payers(): HasMany
    {
        return $this->hasMany(Payer::class);
    }
}
