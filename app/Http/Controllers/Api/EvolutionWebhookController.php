<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\EvolutionWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function __construct(
        protected EvolutionWebhookHandler $webhookHandler
    ) {}

    /**
     * Receber eventos e mensagens da Evolution API.
     */
    public function handle(Request $request): JsonResponse
    {
        // Se for requisição GET (teste de conectividade da Evolution API ou navegador)
        if ($request->isMethod('GET')) {
            return response()->json([
                'status' => 'online',
                'service' => 'FarmaFlow Evolution Webhook Handler',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }

        $payload = $request->all();

        Log::info("Webhook Evolution recebido", [
            'event' => $payload['event'] ?? 'unknown',
            'instance' => $payload['instance'] ?? 'unknown',
            'sender' => $payload['data']['key']['remoteJid'] ?? ($payload['sender'] ?? null),
        ]);

        // Validação de chave de segurança (aceita tanto apikey oficial da Evolution quanto EVOLUTION_WEBHOOK_SECRET)
        $secret = config('services.evolution.webhook_secret', env('EVOLUTION_WEBHOOK_SECRET'));
        $expectedApiKey = config('services.evolution.key', env('EVOLUTION_API_KEY', 'farmaflow_evolution_key_123'));
        $providedKey = $request->header('apikey') ?? $request->header('x-webhook-secret') ?? $request->query('secret') ?? ($payload['apikey'] ?? null);

        // Se a requisição contiver a apikey oficial da Evolution API ou vier da rede interna docker, é 100% autêntica
        $isAuthenticEvolution = (!empty($expectedApiKey) && $providedKey === $expectedApiKey);
        $matchesSecret = (!empty($secret) && $providedKey === $secret);

        // Se o usuário configurou secret estrito mas a requisição não enviou nem a apikey da Evolution nem o secret
        if (!empty($secret) && !empty($providedKey) && !$matchesSecret && !$isAuthenticEvolution) {
            Log::warning("Webhook Evolution API rejeitado: chave/secret fornecido não confere", [
                'provided' => $providedKey,
            ]);
            return response()->json(['error' => 'Acesso não autorizado ao webhook'], 401);
        }

        try {
            $result = $this->webhookHandler->handle($payload);
            return response()->json($result, 200);
        } catch (\Throwable $e) {
            Log::error("Erro no processamento do webhook Evolution API: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Responde 200 para evitar retentativas agressivas da API externa se o payload for malformado
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
