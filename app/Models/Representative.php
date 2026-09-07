<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Representative extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'code',
        'whatsapp_instance',
        'whatsapp_phone',
        'whatsapp_status',
        'whatsapp_connected_at',
        'commission_rate',
        'max_discount_pct',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'max_discount_pct' => 'decimal:2',
            'is_active' => 'boolean',
            'settings' => 'array',
            'whatsapp_connected_at' => 'datetime',
        ];
    }

    /**
     * Retorna o nome da instância do WhatsApp na Evolution API.
     */
    public function getEffectiveWhatsAppInstance(): string
    {
        return !empty($this->whatsapp_instance)
            ? $this->whatsapp_instance
            : config('services.evolution.instance', 'farmaflow');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
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
}
