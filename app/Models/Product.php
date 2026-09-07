<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'sku',
        'name',
        'description',
        'presentation',
        'unit',
        'base_price',
        'stock_quantity',
        'allow_backorder',
        'is_active',
        'category',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'allow_backorder' => 'boolean',
            'is_active' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->orderBy('min_quantity', 'asc');
    }

    public function activePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('is_active', true)
            ->orderBy('min_quantity', 'asc');
    }

    public function campaignProducts(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock_quantity > 0 || $this->allow_backorder);
    }
}
