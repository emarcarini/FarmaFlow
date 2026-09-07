<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'company_id',
        'channel',
        'is_opted_out',
        'opt_out_reason',
        'opted_out_at',
    ];

    protected function casts(): array
    {
        return [
            'is_opted_out' => 'boolean',
            'opted_out_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
