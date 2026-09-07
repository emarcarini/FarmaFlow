<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
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
     * Retorna o status real da conexão do WhatsApp via Evolution API.
     */
    public function status(): JsonResponse
    {
        $status = $this->evolutionApi->getConnectionState();

        return response()->json($status);
    }

    /**
     * Obtém o QR Code ou código de pareamento para conectar o WhatsApp.
     */
    public function getQrCode(): JsonResponse
    {
        $result = $this->evolutionApi->getConnectQrCode();

        return response()->json($result);
    }

    /**
     * Desconecta a sessão atual do WhatsApp.
     */
    public function disconnect(): JsonResponse
    {
        $result = $this->evolutionApi->logoutInstance();

        return response()->json($result);
    }
}
