<?php

namespace App\Services\WhatsApp;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
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
        $this->instance = config('services.evolution.instance', env('EVOLUTION_INSTANCE', 'comercial'));
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

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, [
                'instanceName' => $this->instance,
                'token' => $this->apiKey,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
            ]);

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
    public function getConnectQrCode(): array
    {
        // 1. Tenta obter o QR code diretamente
        $url = "{$this->baseUrl}/instance/connect/{$this->instance}";

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

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'base64' => $data['base64'] ?? null,
                    'code' => $data['code'] ?? null,
                    'pairingCode' => $data['pairingCode'] ?? null,
                    'count' => $data['count'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'message' => 'Não foi possível gerar o QR Code no momento.',
                'details' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
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
                Log::info("Mensagem WhatsApp enviada com sucesso para {$formattedPhone}");
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => $data,
                    'message_id' => $data['key']['id'] ?? ($data['id'] ?? null),
                ];
            }

            Log::warning("Falha ao enviar mensagem WhatsApp para {$formattedPhone}", [
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
