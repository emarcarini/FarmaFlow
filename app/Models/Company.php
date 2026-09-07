<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'representative_id',
        'name',
        'trade_name',
        'document',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'segment',
        'classification',
        'status',
        'notes',
    ];

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function authorizedContacts(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_authorized', true);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(Contact::class)->where('is_primary', true);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(CustomerTag::class, 'taggable', 'taggables');
    }

    public function consentPreferences(): HasMany
    {
        return $this->hasMany(ConsentPreference::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function productHistory(): HasMany
    {
        return $this->hasMany(CustomerProductHistory::class);
    }

    public function score(): HasOne
    {
        return $this->hasOne(CustomerScore::class);
    }
}
