<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    protected $fillable = [
        'external_id',
        'organization_id',
        'title',
        'value',
        'stage',
        'pipeline',
        'currency',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contact::class,
            'deal_contact',
            'deal_id',
            'contact_id'
        )->withPivot('role', 'is_primary')
         ->withTimestamps();
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(DealCustomField::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(CrmMetadata::class);
    }
}
