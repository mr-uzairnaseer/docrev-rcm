<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_order_id',
        'result_code',
        'result_name',
        'value',
        'unit',
        'reference_range',
        'abnormal_flag',
        'status',
        'hl7_oru_message',
        'observed_at',
    ];

    protected $casts = ['observed_at' => 'datetime'];

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }
}
