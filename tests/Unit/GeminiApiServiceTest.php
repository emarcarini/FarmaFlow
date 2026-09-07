<?php

namespace Tests\Unit;

use App\Services\AI\GeminiApiService;
use App\Services\AI\Tools\CommercialTools;
use App\Services\Pricing\PricingEngine;
use App\Services\Sales\OrderService;
use App\Services\Sales\QuoteService;
use App\Services\WhatsApp\HumanHandoverService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiApiServiceTest extends TestCase
{
    protected GeminiApiService $geminiService;
    protected CommercialTools $tools;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-gemini-key']);
        config(['services.gemini.model' => 'gemini-2.0-flash']);

        $pricing = new PricingEngine();
        $quotes = new QuoteService($pricing);
        $orders = new OrderService($quotes, $pricing);
        $handover = new HumanHandoverService();
        $this->tools = new CommercialTools($pricing, $quotes, $orders, $handover);

        $this->geminiService = new GeminiApiService();
    }

    public function test_formats_tools_and_handles_successful_gemini_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'role' => 'model',
                            'parts' => [
                                ['text' => 'Olá! O valor da Dipirona para 10 unidades sai por R$ 10,50.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $tools = $this->tools->getToolDefinitions('customer');
        $contents = [
            [
                'role' => 'user',
                'parts' => [['text' => 'Quanto está 10 caixas de Dipirona?']],
            ],
        ];

        $result = $this->geminiService->generateContent($contents, 'System prompt', $tools);

        $this->assertTrue($result['success']);
        $this->assertEquals(
            'Olá! O valor da Dipirona para 10 unidades sai por R$ 10,50.',
            $result['data']['candidates'][0]['content']['parts'][0]['text']
        );
    }

    public function test_handles_function_calling_from_gemini(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'role' => 'model',
                            'parts' => [
                                [
                                    'functionCall' => [
                                        'name' => 'consultar_preco',
                                        'args' => [
                                            'produto_id' => 1,
                                            'quantidade' => 50,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $tools = $this->tools->getToolDefinitions('customer');
        $result = $this->geminiService->generateContent([
            ['role' => 'user', 'parts' => [['text' => 'Qual o preço para 50 caixas?']]],
        ], 'System prompt', $tools);

        $this->assertTrue($result['success']);
        $functionCall = $result['data']['candidates'][0]['content']['parts'][0]['functionCall'];
        $this->assertEquals('consultar_preco', $functionCall['name']);
        $this->assertEquals(50, $functionCall['args']['quantidade']);
    }
}
