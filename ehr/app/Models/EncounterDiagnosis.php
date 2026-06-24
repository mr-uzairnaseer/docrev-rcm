<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'sequence',
        'icd10_code',
        'description',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
