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
        $this->baseUrl = rtrim(config('services.evolution.url', env('EVOLUTION_API_URL', 'http://localhost:8080')), '/');
        $this->apiKey = config('services.evolution.key', env('EVOLUTION_API_KEY', ''));
        $this->instance = config('services.evolution.instance', env('EVOLUTION_INSTANCE', 'comercial'));
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
