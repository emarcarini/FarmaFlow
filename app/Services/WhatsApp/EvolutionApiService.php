<?php

namespace App\Services\WhatsApp;

use App\Models\AuditLog;
use App\Models\Representative;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.evolution.url', env('EVOLUTION_API_URL', 'http://evolution-api:8080')), '/');
        $this->apiKey = config('services.evolution.key', env('EVOLUTION_API_KEY', 'farmaflow_evolution_key_123'));
        $this->instance = config('services.evolution.instance') ?: (env('EVOLUTION_INSTANCE') ?: 'farmaflow');
    }

    /**
     * Definir explicitamente o nome da instância a ser manipulada.
     */
    public function setInstance(string $instance): self
    {
        $this->instance = trim($instance);
        return $this;
    }

    /**
     * Configurar o serviço para a instância do representante específico.
     */
    public function forRepresentative(?Representative $representative): self
    {
        if ($representative) {
            $this->instance = $representative->getEffectiveWhatsAppInstance();
        } else {
            $this->instance = config('services.evolution.instance') ?: (env('EVOLUTION_INSTANCE') ?: 'farmaflow');
        }
        return $this;
    }

    /**
     * Configurar o serviço para a instância do usuário autenticado.
     */
    public function forUser(?User $user): self
    {
        if ($user && $user->representative) {
            return $this->forRepresentative($user->representative);
        }
        return $this;
    }

    /**
     * Obter a URL base da Evolution API
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Obter o nome da instância atual
     */
    public function getInstanceName(): string
    {
        return $this->instance;
    }

    /**
     * Formatar telefone para padrão internacional WhatsApp (E.164 sem caracteres especiais).
     */
    public function formatPhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // Se tiver 10 ou 11 dígitos no Brasil, adiciona DDI 55
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    /**
     * Obter o estado real da conexão com a Evolution API.
    /**
     * Obter a URL de destino do Webhook para o FarmaFlow.
     */
    /**
     * Obter a URL de destino do Webhook para o FarmaFlow.
     */
    public function getWebhookUrl(): string
    {
        // 1. Variável explícita de webhook
        $customUrl = config('services.evolution.webhook_url') ?: env('EVOLUTION_WEBHOOK_URL');
        if (!empty($customUrl)) {
            return $customUrl;
        }

        // 2. Se a aplicação tiver APP_URL pública configurada (diferente de localhost), usa ela prioritariamente
        $appUrl = config('app.url') ?: env('APP_URL');
        if (!empty($appUrl) && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
            return rtrim($appUrl, '/') . '/api/v1/webhooks/evolution';
        }

        // 3. Se a URL da Evolution API for interna no Docker (ex: http://evolution-api:8080),
        // o webhook usa o container de rede da aplicação (http://app/api/v1/webhooks/evolution)
        if (str_contains($this->baseUrl, 'evolution-api')) {
            return 'http://app/api/v1/webhooks/evolution';
        }

        // 4. Se estiver em requisição HTTP web ativa com domínio real
        if (!app()->runningInConsole()) {
            try {
                $root = request()->root();
                if (!empty($root) && !str_contains($root, 'localhost') && !str_contains($root, '127.0.0.1')) {
                    return rtrim($root, '/') . '/api/v1/webhooks/evolution';
                }
            } catch (\Throwable $e) {
                // Silêncio se não houver contexto HTTP
            }
        }

        return rtrim($appUrl ?: 'http://localhost:8000', '/') . '/api/v1/webhooks/evolution';
    }

    /**
     * Configurar o Webhook na Evolution API para enviar eventos ao FarmaFlow automaticamente.
     */
    public function setWebhookForInstance(?string $instance = null): array
    {
        $instanceName = $instance ?: $this->instance;
        $url = "{$this->baseUrl}/webhook/set/{$instanceName}";
        $webhookUrl = $this->getWebhookUrl();

        $events = [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'MESSAGES_DELETE',
            'SEND_MESSAGE',
            'CONNECTION_UPDATE',
            'QRCODE_UPDATED',
        ];

        try {
            $payload = [
                'enabled' => true,
                'url' => $webhookUrl,
                'webhook_by_events' => false,
                'webhook_base64' => false,
                'webhookByEvents' => false,
                'events' => $events,
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'byEvents' => false,
                    'base64' => false,
                    'events' => $events,
                ],
            ];

            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, $payload);

            Log::info("Webhook Evolution configurado para instancia [{$instanceName}] em [{$webhookUrl}]. Status: {$response->status()} Body: " . $response->body());

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'url' => $webhookUrl,
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Falha ao configurar Webhook Evolution para instancia {$instanceName}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Localizar o webhook ativo na instância na Evolution API (GET /webhook/find/{instance})
     */
    public function findWebhookForInstance(?string $instance = null): array
    {
        $instanceName = $instance ?: $this->instance;
        $url = "{$this->baseUrl}/webhook/find/{$instanceName}";

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->timeout(5)->get($url);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Listar todas as instâncias existentes na Evolution API.
     */
    public function fetchInstances(): array
    {
        $url = "{$this->baseUrl}/instance/fetchInstances";
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->timeout(6)->get($url);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            Log::warning("Erro ao listar instâncias na Evolution API: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deletar uma instância na Evolution API.
     */
    public function deleteInstance(string $instance): array
    {
        $url = "{$this->baseUrl}/instance/delete/{$instance}";
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->timeout(10)->delete($url);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sincronizar o Webhook em todas as instâncias cadastradas no sistema e existentes na Evolution API.
     */
    public function syncAllInstancesWebhooks(): array
    {
        $results = [];

        // 1. Todas as instâncias de Representantes reais
        $reps = Representative::all();
        foreach ($reps as $rep) {
            $inst = $rep->getEffectiveWhatsAppInstance();
            if (!empty($inst)) {
                $results[$inst] = $this->setWebhookForInstance($inst);
            }
        }

        // 2. Buscar todas as instâncias existentes na Evolution API e configurar
        $instances = $this->fetchInstances();
        foreach ($instances as $instData) {
            $name = is_array($instData) ? ($instData['name'] ?? ($instData['instance']['instanceName'] ?? null)) : null;
            if ($name && !isset($results[$name])) {
                $results[$name] = $this->setWebhookForInstance($name);
            }
        }

        return $results;
    }

    /**
     * Obter o estado real da conexão com a Evolution API.
     */
    public function getConnectionState(): array
    {
        $url = "{$this->baseUrl}/instance/connectionState/{$this->instance}";

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(4)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $state = $data['instance']['state'] ?? ($data['state'] ?? 'unknown');
                $isConnected = ($state === 'open');

                // Se estiver conectado, assegura que o webhook está ativo para receber mensagens
                if ($isConnected) {
                    $this->setWebhookForInstance();
                }

                return [
                    'connected' => $isConnected,
                    'state' => $state,
                    'instance' => $this->instance,
                    'server_url' => $this->baseUrl,
                    'online' => true,
                    'details' => $data,
                ];
            }

            // Se retornar 404, a instância pode não ter sido criada ainda
            if ($response->status() === 404) {
                return [
                    'connected' => false,
                    'state' => 'not_created',
                    'instance' => $this->instance,
                    'server_url' => $this->baseUrl,
                    'online' => true,
                    'message' => 'Instância ainda não criada na Evolution API.',
                ];
            }

            return [
                'connected' => false,
                'state' => 'error',
                'instance' => $this->instance,
                'server_url' => $this->baseUrl,
                'online' => true,
                'status_code' => $response->status(),
                'message' => 'Resposta não sucedida da Evolution API.',
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'state' => 'offline',
                'instance' => $this->instance,
                'server_url' => $this->baseUrl,
                'online' => false,
                'message' => 'Evolution API inacessível ou offline: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Criar a instância na Evolution API se não existir.
     */
    public function createInstance(): array
    {
        $url = "{$this->baseUrl}/instance/create";
        $webhookUrl = $this->getWebhookUrl();

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, [
                'instanceName' => $this->instance,
                'token' => $this->apiKey,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'byEvents' => false,
                    'events' => [
                        'MESSAGES_UPSERT',
                        'MESSAGES_UPDATE',
                        'MESSAGES_DELETE',
                        'SEND_MESSAGE',
                        'CONNECTION_UPDATE',
                        'QRCODE_UPDATED',
                    ],
                ],
            ]);

            // Também dispara configuração explícita de webhook
            $this->setWebhookForInstance();

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obter o QR Code da Evolution API para pareamento.
     */
    public function getConnectQrCode(?string $phoneNumber = null): array
    {
        // 1. Tenta obter o QR code diretamente (com número de pareamento se fornecido)
        $url = "{$this->baseUrl}/instance/connect/{$this->instance}";
        if ($phoneNumber) {
            $cleanPhone = preg_replace('/\D+/', '', $phoneNumber);
            if (!empty($cleanPhone)) {
                $url .= "?number={$cleanPhone}";
            }
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get($url);

            if ($response->status() === 404) {
                // Instância não existe, cria ela primeiro
                $this->createInstance();
                $response = Http::withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->get($url);
            }

            // Garante o webhook registrado
            $this->setWebhookForInstance();

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'instance' => $this->instance,
                    'base64' => $data['base64'] ?? null,
                    'code' => $data['code'] ?? null,
                    'pairingCode' => $data['pairingCode'] ?? null,
                    'count' => $data['count'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'instance' => $this->instance,
                'message' => 'Não foi possível gerar o QR Code no momento.',
                'details' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'instance' => $this->instance,
                'message' => 'Erro ao conectar à Evolution API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Desconectar / Logout da instância.
     */
    public function logoutInstance(): array
    {
        $url = "{$this->baseUrl}/instance/logout/{$this->instance}";

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->delete($url);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar mensagem de texto via Evolution API.
     */
    public function sendTextMessage(string $phone, string $text): array
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        $url = "{$this->baseUrl}/message/sendText/{$this->instance}";

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($url, [
                'number' => $formattedPhone,
                'text' => $text,
                'options' => [
                    'delay' => 1200, // delay natural de digitação
                    'presence' => 'composing',
                ],
            ]);

            $statusCode = $response->status();
            $data = $response->json() ?? [];

            if ($response->successful()) {
                Log::info("Mensagem WhatsApp enviada com sucesso para {$formattedPhone} via instância {$this->instance}");
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => $data,
                    'message_id' => $data['key']['id'] ?? ($data['id'] ?? null),
                ];
            }

            Log::warning("Falha ao enviar mensagem WhatsApp para {$formattedPhone} via instância {$this->instance}", [
                'status' => $statusCode,
                'response' => $data,
            ]);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'error' => $data['message'] ?? 'Erro desconhecido na Evolution API',
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção ao comunicar com Evolution API: " . $e->getMessage(), [
                'phone' => $formattedPhone,
                'instance' => $this->instance,
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Simular status de digitação ('composing' ou 'paused').
     */
    public function sendPresence(string $phone, string $presence = 'composing'): bool
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        $url = "{$this->baseUrl}/chat/sendPresence/{$this->instance}";

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post($url, [
                'number' => $formattedPhone,
                'presence' => $presence,
            ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
