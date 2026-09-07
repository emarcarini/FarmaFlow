<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use App\Models\ConsentPreference;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Representative;
use App\Services\AI\CommercialAgentService;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookHandler
{
    public function __construct(
        protected CommercialAgentService $agentService,
        protected EvolutionApiService $whatsappService
    ) {}

    /**
     * Processar payload de webhook recebido da Evolution API de forma idempotente.
     */
    public function handle(array $payload): array
    {
        $event = $payload['event'] ?? 'messages.upsert';

        // Filtrar apenas mensagens de entrada (inbound)
        $data = $payload['data'] ?? $payload;
        $messageKey = $data['key'] ?? [];
        $fromMe = $messageKey['fromMe'] ?? false;

        if ($fromMe) {
            return ['status' => 'ignored_outbound'];
        }

        $messageId = $messageKey['id'] ?? ($data['messageId'] ?? null);
        $remoteJid = $messageKey['remoteJid'] ?? ($data['sender'] ?? '');
        $phone = preg_replace('/\D+/', '', explode('@', $remoteJid)[0]);

        if (empty($phone)) {
            return ['status' => 'invalid_phone'];
        }

        // 1. DEDUPLICAÇÃO & IDEMPOTÊNCIA
        if ($messageId && Message::where('external_id', $messageId)->exists()) {
            Log::info("Webhook Evolution API: Mensagem duplicada ignorada (ID: {$messageId})");
            return ['status' => 'duplicate_ignored', 'message_id' => $messageId];
        }

        // Extrair texto da mensagem
        $messageText = $data['message']['conversation']
            ?? ($data['message']['extendedTextMessage']['text']
            ?? ($data['message']['text'] ?? ''));

        if (empty(trim($messageText))) {
            return ['status' => 'empty_content'];
        }

        return DB::transaction(function () use ($phone, $messageId, $messageText, $data) {
            // 2. Localizar ou criar Contato
            $contact = Contact::where('phone', $phone)
                ->orWhere('phone', 'like', "%" . substr($phone, -8))
                ->first();

            if (!$contact) {
                $defaultRep = Representative::where('is_active', true)->first();
                $contact = Contact::create([
                    'name' => $data['pushName'] ?? "Cliente WhatsApp ({$phone})",
                    'phone' => $phone,
                    'representative_id' => $defaultRep?->id,
                ]);

                ConsentPreference::create([
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                    'is_opted_out' => false,
                ]);
            }

            // 3. Localizar ou criar Conversa
            $conversation = Conversation::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                ],
                [
                    'representative_id' => $contact->representative_id,
                    'status' => 'ai_handling',
                    'last_message_at' => now(),
                ]
            );

            // 4. Salvar mensagem recebida (Idempotência garantida pelo external_id)
            $inboundMessage = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'external_id' => $messageId,
                'sender_type' => 'customer',
                'content' => $messageText,
                'message_type' => 'text',
                'status' => 'received',
                'payload' => $data,
            ]);

            $conversation->update(['last_message_at' => now()]);

            // 5. TRATAMENTO DE OPT-OUT LGPD ("PARAR", "SAIR", "CANCELAR")
            $upper = mb_strtoupper(trim($messageText), 'UTF-8');
            if (in_array($upper, ['PARAR', 'SAIR', 'CANCELAR', 'OPT-OUT', 'DESCADASTRO', 'NÃO ENVIAR MAIS'])) {
                ConsentPreference::updateOrCreate(
                    ['contact_id' => $contact->id, 'channel' => 'whatsapp'],
                    [
                        'is_opted_out' => true,
                        'opt_out_reason' => 'Solicitação explícita por mensagem: ' . $upper,
                        'opted_out_at' => now(),
                    ]
                );

                $optOutReply = "Você solicitou o cancelamento de comunicações automáticas. Suas preferências de privacidade foram atualizadas e não enviaremos novas mensagens automáticas.";
                $this->whatsappService->sendTextMessage($contact->phone, $optOutReply);

                AuditLogService::log(
                    action: 'lgpd.opt_out',
                    auditable: $contact,
                    oldValues: ['is_opted_out' => false],
                    newValues: ['is_opted_out' => true, 'trigger' => $upper],
                    actorType: 'customer',
                    actorName: $contact->name
                );

                return ['status' => 'opt_out_processed'];
            }

            // 6. Se o cliente estiver com opt-out ativo, não prosseguir
            if ($contact->isOptedOut('whatsapp')) {
                Log::info("Contato {$contact->id} possui opt-out de WhatsApp ativo. IA ignorando.");
                return ['status' => 'opted_out_ignored'];
            }

            // 7. Se a conversa estiver em atendimento humano, apenas registrar a mensagem
            if ($conversation->isHumanTakeover()) {
                Log::info("Conversa {$conversation->id} em atendimento humano. Mensagem salva.");
                return ['status' => 'human_takeover_recorded'];
            }

            // 8. Chamar Agente de IA Comercial
            $aiResponse = $this->agentService->handleCustomerMessage($conversation, $messageText);

            return [
                'status' => 'processed',
                'conversation_id' => $conversation->id,
                'response' => $aiResponse,
            ];
        });
    }
}
