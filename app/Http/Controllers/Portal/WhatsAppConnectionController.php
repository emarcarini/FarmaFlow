<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Representative;
use App\Services\WhatsApp\EvolutionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppConnectionController extends Controller
{
    protected EvolutionApiService $evolutionApi;

    public function __construct(EvolutionApiService $evolutionApi)
    {
        $this->evolutionApi = $evolutionApi;
    }

    /**
     * Resolver o representante alvo com base no usuário logado ou parâmetro (para admin).
     */
     protected function resolveRepresentative(Request $request): ?Representative
     {
         $user = Auth::user();

         if ($user->isAdmin()) {
             // 1. Se informou explicitamente o representante pelo ID
             if ($request->filled('representative_id')) {
                 $rep = Representative::find($request->input('representative_id'));
                 if ($rep) return $rep;
             }

             // 2. Se informou pelo nome da instância
             if ($request->filled('instance')) {
                 $rep = Representative::where('whatsapp_instance', $request->input('instance'))->first();
                 if ($rep) return $rep;
             }

             // 3. Prioriza o representante com WhatsApp já conectado ou com instância configurada
             return Representative::where('whatsapp_status', 'open')->first()
                 ?? Representative::whereNotNull('whatsapp_instance')->first()
                 ?? Representative::where('is_active', true)->first();
         }

         return $user->representative;
     }

    /**
     * Retorna o status real da conexão do WhatsApp via Evolution API.
     */
    public function status(Request $request): JsonResponse
    {
        // 1. Sincroniza em tempo real com as instâncias existentes na Evolution API
        $liveInstances = $this->evolutionApi->fetchInstances();
        $connectedInstances = [];

        foreach ($liveInstances as $inst) {
            $name = is_array($inst) ? ($inst['name'] ?? ($inst['instance']['instanceName'] ?? null)) : null;
            $connStatus = is_array($inst) ? ($inst['connectionStatus'] ?? ($inst['instance']['state'] ?? ($inst['state'] ?? 'close'))) : 'close';

            if ($name) {
                $isOpen = ($connStatus === 'open');
                if ($isOpen) {
                    $connectedInstances[] = $name;
                }

                $matchingRep = Representative::where('whatsapp_instance', $name)->first();
                if ($matchingRep) {
                    $matchingRep->update([
                        'whatsapp_status' => $isOpen ? 'open' : 'disconnected',
                        'whatsapp_connected_at' => $isOpen ? ($matchingRep->whatsapp_connected_at ?? now()) : $matchingRep->whatsapp_connected_at,
                    ]);
                }
            }
        }

        // 2. Se o usuário não especificou representative_id e existe um representante conectado, prioriza ele
        if (!$request->filled('representative_id') && count($connectedInstances) > 0) {
            $firstConnectedRep = Representative::whereIn('whatsapp_instance', $connectedInstances)->first();
            if ($firstConnectedRep) {
                $request->merge(['representative_id' => $firstConnectedRep->id]);
            }
        }

        $rep = $this->resolveRepresentative($request);
        $this->evolutionApi->forRepresentative($rep);

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

        $status['representative_name'] = $rep?->name ?? 'Geral';
        $status['representative_id'] = $rep?->id;
        $status['all_representatives'] = Representative::select('id', 'name', 'code', 'whatsapp_instance', 'whatsapp_status')->get();
        $status['live_instances'] = $liveInstances;

        return response()->json($status);
    }

    /**
     * Obtém o QR Code ou código de pareamento para conectar o WhatsApp.
     */
    public function getQrCode(Request $request): JsonResponse
    {
        $rep = $this->resolveRepresentative($request);
        $this->evolutionApi->forRepresentative($rep);

        $result = $this->evolutionApi->getConnectQrCode();
        $result['representative_name'] = $rep?->name ?? 'Geral';

        return response()->json($result);
    }

    /**
     * Desconecta a sessão atual do WhatsApp.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $rep = $this->resolveRepresentative($request);
        $this->evolutionApi->forRepresentative($rep);

        $result = $this->evolutionApi->logoutInstance();

        if ($rep) {
            $rep->update([
                'whatsapp_status' => 'disconnected',
            ]);
        }

        return response()->json($result);
    }

    /**
     * Sincroniza webhooks para todas as instâncias da Evolution API.
     */
    public function syncWebhooks(): JsonResponse
    {
        $results = $this->evolutionApi->syncAllInstancesWebhooks();
        return response()->json([
            'success' => true,
            'message' => 'Webhooks sincronizados com sucesso em todas as instâncias.',
            'results' => $results,
        ]);
    }

    /**
     * Deletar uma instância órfã ou desconectada na Evolution API.
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
