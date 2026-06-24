<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsRegion extends Model
{
    protected $fillable = ['number', 'name'];

    public function states(): HasMany
    {
        return $this->hasMany(CmsState::class);
    }
}
