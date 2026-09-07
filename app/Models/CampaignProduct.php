<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_campaign_id',
        'product_id',
        'special_price',
        'discount_pct',
        'min_quantity',
        'max_quantity',
    ];

    protected function casts(): array
    {
        return [
            'special_price' => 'decimal:2',
            'discount_pct' => 'decimal:2',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CommercialCampaign::class, 'commercial_campaign_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function matchesQuantity(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity !== null && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }
}
