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
        $event = strtolower($payload['event'] ?? 'messages.upsert');

        // Se for evento de conexão (CONNECTION_UPDATE / QRCODE_UPDATED), registra e retorna
        if (str_contains($event, 'connection') || str_contains($event, 'qrcode')) {
            Log::info("Webhook Evolution status de conexao recebido: {$event}", [
                'instance' => $payload['instance'] ?? 'unknown',
            ]);
            return ['status' => 'connection_event_received'];
        }

        // Desembrulhar payload de mensagens
        $data = $payload['data'] ?? $payload;
        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        // Filtrar apenas mensagens de entrada (inbound)
        $messageKey = $data['key'] ?? [];
        $fromMe = $messageKey['fromMe'] ?? ($data['fromMe'] ?? false);

        if ($fromMe) {
            return ['status' => 'ignored_outbound'];
        }

        $messageId = $messageKey['id'] ?? ($data['messageId'] ?? ($data['id'] ?? null));
        $remoteJid = $messageKey['remoteJid'] ?? ($data['sender'] ?? ($data['remoteJid'] ?? ''));
        $phone = preg_replace('/\D+/', '', explode('@', $remoteJid)[0]);

        if (empty($phone)) {
            Log::warning("Webhook Evolution: RemoteJid/Telefone vazio no payload", ['data' => $data]);
            return ['status' => 'invalid_phone'];
        }

        // 1. DEDUPLICAÇÃO & IDEMPOTÊNCIA
        if ($messageId && Message::where('external_id', $messageId)->exists()) {
            Log::info("Webhook Evolution API: Mensagem duplicada ignorada (ID: {$messageId})");
            return ['status' => 'duplicate_ignored', 'message_id' => $messageId];
        }

        // Extrair texto da mensagem em múltiplos formatos suportados pela Evolution API v2
        $messageObj = $data['message'] ?? [];
        $messageText = $messageObj['conversation']
            ?? ($messageObj['extendedTextMessage']['text']
            ?? ($messageObj['imageMessage']['caption']
            ?? ($messageObj['videoMessage']['caption']
            ?? ($messageObj['documentMessage']['caption']
            ?? ($data['text'] ?? ($data['body'] ?? ''))))));

        if (empty(trim((string) $messageText))) {
            Log::info("Webhook Evolution: Conteúdo da mensagem vazio ou não textual", ['messageObj' => $messageObj]);
            return ['status' => 'empty_content'];
        }

        // 2. Identificar a instância do representante no payload
        $instanceName = $payload['instance'] ?? ($data['instance'] ?? ($payload['owner'] ?? null));
        $targetRep = null;
        if ($instanceName) {
            $targetRep = Representative::where('whatsapp_instance', $instanceName)
                ->orWhere('code', $instanceName)
                ->orWhere('id', str_replace('rep_', '', $instanceName))
                ->first();
        }
        if (!$targetRep) {
            $targetRep = Representative::where('is_active', true)->first();
        }

        // Configura o serviço de WhatsApp para responder usando a instância correta do representante
        $this->whatsappService->forRepresentative($targetRep);

        return DB::transaction(function () use ($phone, $messageId, $messageText, $data, $targetRep) {
            // 3. Localizar ou criar Contato
            $contact = Contact::where('phone', $phone)
                ->orWhere('phone', 'like', "%" . substr($phone, -8))
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'name' => $data['pushName'] ?? "Cliente WhatsApp ({$phone})",
                    'phone' => $phone,
                    'representative_id' => $targetRep?->id,
                ]);

                ConsentPreference::create([
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                    'is_opted_out' => false,
                ]);
            } elseif (!$contact->representative_id && $targetRep) {
                $contact->update(['representative_id' => $targetRep->id]);
            }

            // 4. Localizar ou criar Conversa
            $conversation = Conversation::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                ],
                [
                    'representative_id' => $contact->representative_id ?? $targetRep?->id,
                    'status' => 'ai_handling',
                    'last_message_at' => now(),
                ]
            );

            // 5. Salvar mensagem recebida (Idempotência garantida pelo external_id)
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

            // 6. TRATAMENTO DE OPT-OUT LGPD ("PARAR", "SAIR", "CANCELAR")
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

            // 7. Se o cliente estiver com opt-out ativo, não prosseguir
            if ($contact->isOptedOut('whatsapp')) {
                Log::info("Contato {$contact->id} possui opt-out de WhatsApp ativo. IA ignorando.");
                return ['status' => 'opted_out_ignored'];
            }

            // 8. Se a conversa estiver em atendimento humano, apenas registrar a mensagem
            if ($conversation->isHumanTakeover()) {
                Log::info("Conversa {$conversation->id} em atendimento humano. Mensagem salva.");
                return ['status' => 'human_takeover_recorded'];
            }

            // 9. Chamar Agente de IA Comercial
            $aiResponse = $this->agentService->handleCustomerMessage($conversation, $messageText);

            return [
                'status' => 'processed',
                'conversation_id' => $conversation->id,
                'response' => $aiResponse,
            ];
        });
    }
}
