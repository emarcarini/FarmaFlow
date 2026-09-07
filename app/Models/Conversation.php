<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'representative_id',
        'channel',
        'external_id',
        'status',
        'handover_reason',
        'handover_at',
        'resumed_at',
        'last_message_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'handover_at' => 'datetime',
            'resumed_at' => 'datetime',
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function isAiHandling(): bool
    {
        return $this->status === 'ai_handling';
    }

    public function isHumanTakeover(): bool
    {
        return $this->status === 'human_takeover';
    }

    public function triggerHandover(string $reason): void
    {
        $this->update([
            'status' => 'human_takeover',
            'handover_reason' => $reason,
            'handover_at' => now(),
        ]);
    }

    public function resumeAi(): void
    {
        $this->update([
            'status' => 'ai_handling',
            'resumed_at' => now(),
        ]);
    }
}
