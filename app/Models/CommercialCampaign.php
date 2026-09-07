<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'rules' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(CampaignAudience::class);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function isEligibleForCustomer(?Company $company = null, ?Contact $contact = null): bool
    {
        // Se a campanha não tiver audiência específica configurada, ela é aberta para todos os clientes
        if ($this->audiences()->count() === 0) {
            return true;
        }

        if ($company) {
            if ($this->audiences()->where('company_id', $company->id)->exists()) {
                return true;
            }

            if ($company->segment && $this->audiences()->where('segment', $company->segment)->exists()) {
                return true;
            }

            if ($company->classification && $this->audiences()->where('classification', $company->classification)->exists()) {
                return true;
            }
        }

        if ($contact && $this->audiences()->where('contact_id', $contact->id)->exists()) {
            return true;
        }

        return false;
    }
}
