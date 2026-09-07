<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Representative;
use App\Services\WhatsApp\EvolutionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppConnectionController extends Controller
{
    protected EvolutionApiService $evolutionApi;

    public function __construct(EvolutionApiService $evolutionApi)
    {
        $this->evolutionApi = $evolutionApi;
    }

    /**
     * Obter o representante titular (Emmanuel Marcarini).
     */
    protected function getTargetRepresentative(): ?Representative
    {
        return Representative::where('whatsapp_instance', 'farmaflow')->first()
            ?? Representative::where('code', 'FARMAFLOW')->first()
            ?? Representative::first();
    }

    /**
     * Retorna o status real da conexão do WhatsApp via Evolution API.
     */
    public function status(Request $request): JsonResponse
    {
        $rep = $this->getTargetRepresentative();
        $instanceName = $rep?->whatsapp_instance ?: config('services.evolution.instance', 'farmaflow');

        $this->evolutionApi->setInstance($instanceName);
        $status = $this->evolutionApi->getConnectionState();

        // Se o status for open, atualiza o timestamp no banco
        if ($rep && ($status['connected'] ?? false)) {
            $rep->update([
                'whatsapp_status' => 'open',
                'whatsapp_connected_at' => $rep->whatsapp_connected_at ?? now(),
            ]);
        } elseif ($rep) {
            $rep->update([
                'whatsapp_status' => $status['state'] ?? 'disconnected',
            ]);
        }

        $liveInstances = $this->evolutionApi->fetchInstances();

        $status['representative_name'] = $rep?->name ?? 'Emmanuel Marcarini';
        $status['representative_id'] = $rep?->id;
        $status['atendente_phone'] = $rep?->whatsapp_phone ?? '5528999158412';
        $status['instance'] = $instanceName;
        $status['live_instances'] = $liveInstances;

        return response()->json($status);
    }

    /**
     * Obtém o QR Code ou código de pareamento para conectar o WhatsApp.
     */
    public function getQrCode(Request $request): JsonResponse
    {
        $rep = $this->getTargetRepresentative();
        $instanceName = $rep?->whatsapp_instance ?: config('services.evolution.instance', 'farmaflow');

        $this->evolutionApi->setInstance($instanceName);

        $phone = $rep?->whatsapp_phone ?? '5528999158412';
        $result = $this->evolutionApi->getConnectQrCode($phone);
        $result['representative_name'] = $rep?->name ?? 'Emmanuel Marcarini';
        $result['atendente_phone'] = $phone;
        $result['instance'] = $instanceName;

        return response()->json($result);
    }

    /**
     * Desconecta a sessão atual do WhatsApp.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $rep = $this->getTargetRepresentative();
        $instanceName = $rep?->whatsapp_instance ?: config('services.evolution.instance', 'farmaflow');

        $this->evolutionApi->setInstance($instanceName);
        $result = $this->evolutionApi->logoutInstance();

        if ($rep) {
            $rep->update([
                'whatsapp_status' => 'disconnected',
            ]);
        }

        return response()->json($result);
    }

    /**
     * Sincroniza webhooks para a instância farmaflow na Evolution API.
     */
    public function syncWebhooks(): JsonResponse
    {
        $this->evolutionApi->setInstance('farmaflow');
        $result = $this->evolutionApi->setWebhookForInstance();

        return response()->json([
            'success' => true,
            'message' => 'Webhooks sincronizados com sucesso na instância farmaflow.',
            'result' => $result,
        ]);
    }

    /**
     * Deletar uma instância órfã ou antiga na Evolution API.
     */
    public function deleteOrphanInstance(Request $request): JsonResponse
    {
        $instanceName = $request->input('instance');
        if (empty($instanceName)) {
            return response()->json(['error' => 'Nome da instância não informado'], 400);
        }

        $result = $this->evolutionApi->deleteInstance($instanceName);
        return response()->json($result);
    }
}
