<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'discount_pct',
        'valid_from',
        'valid_until',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_pct' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isValidForDate(?\DateTimeInterface $date = null): bool
    {
        $checkDate = $date ? $date->format('Y-m-d') : now()->format('Y-m-d');

        if ($this->valid_from && $this->valid_from->format('Y-m-d') > $checkDate) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->format('Y-m-d') < $checkDate) {
            return false;
        }

        return $this->is_active;
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
