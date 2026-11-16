<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected $fillable = [
        'external_id',
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(
            Deal::class,
            'deal_contact',
            'contact_id',
            'deal_id'
        )->withPivot('role', 'is_primary')
         ->withTimestamps();
    }
}
