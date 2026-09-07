<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductHistory extends Model
{
    use HasFactory;

    protected $table = 'customer_product_history';

    protected $fillable = [
        'company_id',
        'contact_id',
        'product_id',
        'last_order_id',
        'last_purchased_at',
        'total_quantity_purchased',
        'total_spent',
        'average_cycle_days',
        'next_estimated_purchase_at',
    ];

    protected function casts(): array
    {
        return [
            'last_purchased_at' => 'datetime',
            'next_estimated_purchase_at' => 'date',
            'total_quantity_purchased' => 'integer',
            'total_spent' => 'decimal:2',
            'average_cycle_days' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lastOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'last_order_id');
    }
}
