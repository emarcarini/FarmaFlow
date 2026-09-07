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

        // 1. Identificar a instância e representante do payload logo no início
        $data = $payload['data'] ?? $payload;
        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $instanceName = $payload['instance'] ?? ($data['instance'] ?? ($payload['owner'] ?? config('services.evolution.instance', 'farmaflow')));
        if (empty($instanceName)) {
            $instanceName = 'farmaflow';
        }

        $targetRep = Representative::where('whatsapp_instance', 'farmaflow')
            ->orWhere('whatsapp_instance', $instanceName)
            ->orWhere('code', 'FARMAFLOW')
            ->first() ?? Representative::first();

        // Se o representante ainda não tem instância gravada e recebemos uma, atualiza
        if ($targetRep && empty($targetRep->whatsapp_instance) && $instanceName) {
            $targetRep->update(['whatsapp_instance' => $instanceName]);
        }

        // Configura a instância correta no serviço WhatsApp
        $this->whatsappService->setInstance($instanceName);

        // Se for evento de conexão (CONNECTION_UPDATE / QRCODE_UPDATED), registra e retorna
        if (str_contains($event, 'connection') || str_contains($event, 'qrcode')) {
            Log::info("Webhook Evolution status de conexao recebido: {$event}", [
                'instance' => $instanceName ?? 'unknown',
            ]);
            return ['status' => 'connection_event_received'];
        }

        // Se for evento de contatos (CONTACTS_UPDATE / CONTACTS_UPSERT)
        if (str_contains($event, 'contact')) {
            Log::info("Webhook Evolution contatos recebido: {$event}", ['instance' => $instanceName]);
            $rawList = $payload['data'] ?? [];
            if (is_array($rawList)) {
                $items = isset($rawList['remoteJid']) ? [$rawList] : $rawList;
                foreach ($items as $c) {
                    if (is_array($c) && !empty($c['remoteJid']) && !empty($c['pushName'])) {
                        $cPhone = preg_replace('/\D+/', '', explode('@', $c['remoteJid'])[0]);
                        if (!empty($cPhone) && strlen($cPhone) >= 10 && $cPhone !== '0') {
                            $contact = Contact::where('phone', $cPhone)
                                ->orWhere('phone', 'like', "%" . substr($cPhone, -8))
                                ->first();

                            if ($contact) {
                                $contact->update([
                                    'name' => $c['pushName'],
                                    'representative_id' => $contact->representative_id ?? $targetRep?->id,
                                ]);
                            } else {
                                $contact = Contact::create([
                                    'name' => $c['pushName'],
                                    'phone' => $cPhone,
                                    'representative_id' => $targetRep?->id,
                                ]);

                                ConsentPreference::create([
                                    'contact_id' => $contact->id,
                                    'channel' => 'whatsapp',
                                    'is_opted_out' => false,
                                ]);
                            }
                        }
                    }
                }
            }
            return ['status' => 'contacts_synced'];
        }

        // Se for evento de chat genérico sem mensagem
        if (str_contains($event, 'chat') && !str_contains($event, 'message')) {
            return ['status' => 'chats_event_received'];
        }

        // Filtrar apenas mensagens de entrada (inbound)
        $messageKey = $data['key'] ?? [];
        $fromMe = $messageKey['fromMe'] ?? ($data['fromMe'] ?? false);

        if ($fromMe) {
            return ['status' => 'ignored_outbound'];
        }

        $messageId = $messageKey['id'] ?? ($data['messageId'] ?? ($data['id'] ?? null));
        $remoteJid = $messageKey['remoteJid'] ?? ($data['sender'] ?? ($data['remoteJid'] ?? ''));
        $senderPn = $messageKey['senderPn'] ?? ($data['senderPn'] ?? ($data['participantPn'] ?? null));

        // Se o remoteJid for ID interno do WhatsApp (@lid), usa o número de telefone real (senderPn)
        if ((empty($remoteJid) || str_contains($remoteJid, '@lid')) && !empty($senderPn)) {
            $remoteJid = $senderPn;
        }

        $phone = preg_replace('/\D+/', '', explode('@', $remoteJid)[0]);

        if (empty($phone)) {
            Log::warning("Webhook Evolution: RemoteJid/Telefone vazio no payload", ['data' => $data]);
            return ['status' => 'invalid_phone'];
        }

        // 2. DEDUPLICAÇÃO & IDEMPOTÊNCIA
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

        $isAdmin = self::isAdminPhone($phone);

        // 3. PERSISTÊNCIA COMPLETA E IMEDIATA (Contato, Conversa, Mensagem)
        // Salva primeiro para garantir que mesmo em falha de IA/envio o registro apareça no Inbox
        [$contact, $conversation, $inboundMessage] = DB::transaction(function () use ($phone, $messageId, $messageText, $data, $targetRep, $isAdmin) {
            $contact = Contact::where('phone', $phone)
                ->orWhere('phone', 'like', "%" . substr($phone, -8))
                ->first();

            $contactName = $isAdmin 
                ? 'Emmanuel Marcarini (Administrador)' 
                : ($data['pushName'] ?? "Cliente WhatsApp ({$phone})");

            if (!$contact) {
                $contact = Contact::create([
                    'name' => $contactName,
                    'phone' => $phone,
                    'representative_id' => $targetRep?->id,
                    'role_position' => $isAdmin ? 'Administrador Geral' : null,
                ]);

                ConsentPreference::create([
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                    'is_opted_out' => false,
                ]);
            } else {
                $updates = [];
                if ($isAdmin && !str_contains($contact->name, 'Administrador')) {
                    $updates['name'] = 'Emmanuel Marcarini (Administrador)';
                    $updates['role_position'] = 'Administrador Geral';
                }
                if (!$contact->representative_id && $targetRep) {
                    $updates['representative_id'] = $targetRep->id;
                }
                if (!empty($updates)) {
                    $contact->update($updates);
                }
            }

            $conversation = Conversation::firstOrCreate(
                [
                    'contact_id' => $contact->id,
                    'channel' => 'whatsapp',
                ],
                [
                    'representative_id' => $contact->representative_id ?? $targetRep?->id,
                    'status' => 'ai_handling',
                    'last_message_at' => now(),
                    'metadata' => $isAdmin ? ['is_admin' => true] : null,
                ]
            );

            // Se for admin, garante que a conversa está ativa para IA responder o admin
            if ($isAdmin && $conversation->isHumanTakeover()) {
                $conversation->update(['status' => 'ai_handling']);
            }

            $inboundMessage = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'external_id' => $messageId,
                'sender_type' => $isAdmin ? 'representative' : 'customer',
                'content' => $messageText,
                'message_type' => 'text',
                'status' => 'received',
                'payload' => $data,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return [$contact, $conversation, $inboundMessage];
        });

        // 4. TRATAMENTO DE OPT-OUT LGPD ("PARAR", "SAIR", "CANCELAR")
        $upper = mb_strtoupper(trim($messageText), 'UTF-8');
        if (!$isAdmin && in_array($upper, ['PARAR', 'SAIR', 'CANCELAR', 'OPT-OUT', 'DESCADASTRO', 'NÃO ENVIAR MAIS'])) {
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

        // 5. Se o cliente estiver com opt-out ativo, não prosseguir
        if (!$isAdmin && $contact->isOptedOut('whatsapp')) {
            Log::info("Contato {$contact->id} possui opt-out de WhatsApp ativo. IA ignorando.");
            return ['status' => 'opted_out_ignored'];
        }

        // 6. Se a conversa estiver em atendimento humano, apenas registrar a mensagem (exceto se for o Admin conversando com o Bot)
        if (!$isAdmin && $conversation->isHumanTakeover()) {
            Log::info("Conversa {$conversation->id} em atendimento humano. Mensagem salva.");
            return ['status' => 'human_takeover_recorded'];
        }

        // 7. Chamar Agente de IA Comercial / Copiloto Admin
        try {
            $aiResponse = $this->agentService->handleCustomerMessage(
                conversation: $conversation,
                userMessage: $messageText,
                instanceName: $instanceName,
                isAdmin: $isAdmin
            );

            return [
                'status' => 'processed',
                'conversation_id' => $conversation->id,
                'is_admin' => $isAdmin,
                'response' => $aiResponse,
            ];
        } catch (\Throwable $e) {
            Log::error("Erro na geração de resposta pelo Agente de IA: " . $e->getMessage(), [
                'conversation_id' => $conversation->id,
                'exception' => $e,
            ]);

            return [
                'status' => 'saved_ai_error',
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar se o número de telefone pertence ao Administrador / Gestor do sistema.
     */
    public static function isAdminPhone(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $clean = preg_replace('/\D+/', '', $phone);
        if (empty($clean)) {
            return false;
        }

        // Número direto de Emmanuel Marcarini (com ou sem DDI 55)
        if (str_ends_with($clean, '28999439677') || str_ends_with($clean, '999439677')) {
            return true;
        }

        // Variável de ambiente configurada no .env (ADMIN_PHONE)
        $envAdmin = env('ADMIN_PHONE');
        if (!empty($envAdmin)) {
            $cleanEnv = preg_replace('/\D+/', '', $envAdmin);
            if (!empty($cleanEnv) && (str_ends_with($clean, $cleanEnv) || str_ends_with($cleanEnv, $clean))) {
                return true;
            }
        }

        // Busca em usuários admin cadastrados no banco
        try {
            $adminUsers = \App\Models\User::where('role', 'admin')->whereNotNull('phone')->get();
            foreach ($adminUsers as $u) {
                $uClean = preg_replace('/\D+/', '', $u->phone);
                if (!empty($uClean) && (str_ends_with($clean, $uClean) || str_ends_with($uClean, $clean))) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Em caso de falha de conexão com o banco
        }

        return false;
    }
}
