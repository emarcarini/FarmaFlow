<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contact_id',
        'recency_score',
        'frequency_score',
        'monetary_score',
        'overall_score',
        'trend',
        'explanation',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'recency_score' => 'integer',
            'frequency_score' => 'integer',
            'monetary_score' => 'integer',
            'overall_score' => 'integer',
            'explanation' => 'array',
            'calculated_at' => 'datetime',
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
}
