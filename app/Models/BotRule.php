<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'is_preset',
        'representative_id',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_preset' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public function representative()
    {
        return $this->belongsTo(Representative::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePreset($query)
    {
        return $query->where('is_preset', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_preset', false);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
