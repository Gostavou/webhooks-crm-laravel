<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMetadata extends Model
{
    protected $table = 'metadata';

    protected $fillable = [
        'deal_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'meta_value' => 'json',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
