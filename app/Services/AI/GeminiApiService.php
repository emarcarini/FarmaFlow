<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (config('services.gemini.key') ?? env('GEMINI_API_KEY', ''));
        $this->model = $model ?? (config('services.gemini.model') ?? env('GEMINI_MODEL', 'gemini-2.0-flash'));
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'fake-key';
    }

    /**
     * Enviar requisição para o Google Gemini API com suporte a Function Calling.
     */
    public function generateContent(
        array $contents,
        ?string $systemInstruction = null,
        ?array $tools = null,
        float $temperature = 0.3
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'API Key do Gemini não configurada.',
            ];
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
            ],
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        if (!empty($tools)) {
            $payload['tools'] = [
                [
                    'function_declarations' => $this->formatToolsForGemini($tools),
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error("Erro na API do Google Gemini: " . $response->body(), [
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'Erro na comunicação com a API do Google Gemini.',
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção ao chamar Google Gemini: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Converter definições de ferramentas para o formato do Gemini Function Declarations.
     */
    protected function formatToolsForGemini(array $tools): array
    {
        $declarations = [];

        foreach ($tools as $tool) {
            $fn = $tool['function'] ?? $tool;
            $declarations[] = [
                'name' => $fn['name'],
                'description' => $fn['description'] ?? '',
                'parameters' => $this->normalizeParameters($fn['parameters'] ?? []),
            ];
        }

        return $declarations;
    }

    /**
     * Normalizar tipos de parâmetros (STRING, INTEGER, OBJECT, ARRAY, BOOLEAN).
     */
    protected function normalizeParameters(array $params): array
    {
        if (empty($params)) {
            return [
                'type' => 'OBJECT',
                'properties' => (object) [],
            ];
        }

        $type = strtoupper($params['type'] ?? 'OBJECT');
        $normalized = [
            'type' => $type,
        ];

        if (!empty($params['description'])) {
            $normalized['description'] = $params['description'];
        }

        if (!empty($params['required'])) {
            $normalized['required'] = $params['required'];
        }

        if (!empty($params['properties'])) {
            $normalized['properties'] = [];
            foreach ($params['properties'] as $propName => $propDef) {
                $normalized['properties'][$propName] = $this->normalizeParameters($propDef);
            }
        }

        if (!empty($params['items'])) {
            $normalized['items'] = $this->normalizeParameters($params['items']);
        }

        if (!empty($params['enum'])) {
            $normalized['enum'] = $params['enum'];
        }

        return $normalized;
    }
}
