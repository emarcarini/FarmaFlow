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
        $payload = $request->all();

        // Opcional: Validação de chave de segurança / secret configurado
        $secret = config('services.evolution.webhook_secret', env('EVOLUTION_WEBHOOK_SECRET'));
        $providedSecret = $request->header('x-webhook-secret') ?? $request->query('secret');

        if (!empty($secret) && $providedSecret !== $secret) {
            Log::warning("Webhook Evolution API rejeitado: secret inválido");
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
