<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Log a commercial action.
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $actorType = 'user',
        ?string $actorName = null,
        ?int $userId = null
    ): AuditLog {
        $user = Auth::user();
        
        $resolvedUserId = $userId ?? ($user ? $user->id : null);
        $resolvedActorName = $actorName ?? ($user ? $user->name : ($actorType === 'ai' ? 'Agente IA Comercial' : 'Sistema'));
        $resolvedActorType = $actorType;

        return AuditLog::create([
            'user_id' => $resolvedUserId,
            'actor_type' => $resolvedActorType,
            'actor_name' => $resolvedActorName,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent() ?? 'CLI/Internal',
        ]);
    }

    /**
     * Log an action executed by the AI Agent.
     */
    public static function logAiAction(
        string $action,
        ?Model $auditable = null,
        ?array $details = null
    ): AuditLog {
        return self::log(
            action: $action,
            auditable: $auditable,
            oldValues: null,
            newValues: $details,
            actorType: 'ai',
            actorName: 'Agente IA Comercial'
        );
    }
}
