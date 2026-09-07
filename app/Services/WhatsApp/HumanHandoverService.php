<?php

namespace App\Services\WhatsApp;

use App\Models\Conversation;
use App\Models\Notification;
use App\Services\Audit\AuditLogService;

class HumanHandoverService
{
    /**
     * Acionar transferência para humano (pausa automática do robô).
     */
    public function triggerHandover(Conversation $conversation, string $reason, ?string $notes = null): void
    {
        $conversation->load('contact.company', 'contact.representative.user');

        $conversation->update([
            'status' => 'human_takeover',
            'handover_reason' => $reason,
            'handover_at' => now(),
            'metadata' => array_merge($conversation->metadata ?? [], [
                'handover_notes' => $notes,
            ]),
        ]);

        // Notificar o representante responsável ou administradores
        $repUser = $conversation->contact?->representative?->user;
        if ($repUser) {
            Notification::create([
                'user_id' => $repUser->id,
                'title' => "Atendimento Humano Solicitado: {$conversation->contact?->name}",
                'body' => "O cliente solicitou atendimento humano ou acionou gatilho de exceção: '{$reason}'. A IA foi pausada.",
                'type' => 'handover',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'contact_id' => $conversation->contact_id,
                    'reason' => $reason,
                ],
            ]);
        }

        AuditLogService::log(
            action: 'whatsapp.human_handover',
            auditable: $conversation,
            oldValues: ['status' => 'ai_handling'],
            newValues: ['status' => 'human_takeover', 'reason' => $reason],
            actorType: 'system',
            actorName: 'HumanHandoverService'
        );
    }

    /**
     * Retomar o atendimento automático da IA.
     */
    public function resumeAi(Conversation $conversation): void
    {
        $conversation->update([
            'status' => 'ai_handling',
            'resumed_at' => now(),
        ]);

        AuditLogService::log(
            action: 'whatsapp.ai_resumed',
            auditable: $conversation,
            oldValues: ['status' => 'human_takeover'],
            newValues: ['status' => 'ai_handling'],
            actorType: 'user'
        );
    }
}
