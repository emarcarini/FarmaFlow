<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'representative_id',
        'name',
        'role_position',
        'phone',
        'email',
        'is_primary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function activeConversation(): HasOne
    {
        return $this->hasOne(Conversation::class)->latestOfMany();
    }

    public function aiMemories(): HasMany
    {
        return $this->hasMany(AiMemory::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(CustomerTag::class, 'taggable', 'taggables');
    }

    public function consentPreferences(): HasMany
    {
        return $this->hasMany(ConsentPreference::class);
    }

    public function isOptedOut(string $channel = 'whatsapp'): bool
    {
        return $this->consentPreferences()
            ->where('channel', $channel)
            ->where('is_opted_out', true)
            ->exists();
    }
}
