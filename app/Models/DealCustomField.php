<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealCustomField extends Model
{
    protected $fillable = [
        'deal_id',
        'field_key',
        'field_value',
    ];

    protected $casts = [
        'field_value' => 'json',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
