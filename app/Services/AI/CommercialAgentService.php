<?php

namespace App\Services\AI;

use App\Models\AiMemory;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\Tools\CommercialTools;
use App\Services\Audit\AuditLogService;
use App\Services\WhatsApp\EvolutionApiService;
use App\Services\WhatsApp\HumanHandoverService;
use Illuminate\Support\Facades\Log;

class CommercialAgentService
{
    public function __construct(
        protected CommercialTools $tools,
        protected EvolutionApiService $whatsappService,
        protected HumanHandoverService $handoverService,
        protected GeminiApiService $geminiService
    ) {}

    /**
     * Verificar se a mensagem contém dúvidas clínicas, diagnóstico ou prescrição médica (Firewall Clínico).
     */
    public function checkClinicalSafety(string $message): bool
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        $patterns = [
            '/\b(posso tomar|quantas gotas|qual dosagem|posso dar para|receita médica|diagn[oó]stico|estou com dor|sinto dor|estou com febre|sintomas de|tratamento para|contraindicad[oa])\b/i',
            '/\b(gr[aá]vida pode|crian[çc]a de \d+ anos pode|posso misturar com|efeito colateral|bula de|superdosagem)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true; // Violação detectada
            }
        }

        return false;
    }

    /**
     * Processar mensagem recebida do cliente no WhatsApp.
     */
    public function handleCustomerMessage(Conversation $conversation, string $userMessage, ?string $instanceName = null): string
    {
        $contact = $conversation->contact;
        $company = $contact?->company;
        $representative = $conversation->representative ?? $contact?->representative;

        // 1. Verificar se a conversa está pausada para atendimento humano
        if ($conversation->isHumanTakeover()) {
            Log::info("Conversa #{$conversation->id} em atendimento humano. IA ignorando resposta automática.");
            return '';
        }

        // 2. FIREWALL CLÍNICO: Salvaguarda estrita contra diagnósticos ou prescrições
        if ($this->checkClinicalSafety($userMessage)) {
            $this->handoverService->triggerHandover($conversation, 'clinical_inquiry_firewall', 'Cliente solicitou orientação clínica/médica.');

            $safetyResponse = "Por questões de segurança regulatória e saúde, nosso assistente comercial não fornece orientações diagnósticas, posologias ou prescrições médicas. Vou transferir seu contato para a nossa equipe especializada.";

            $this->saveAndSendMessage($conversation, $safetyResponse, $instanceName);

            AuditLogService::log(
                action: 'clinical_firewall.triggered',
                auditable: $conversation,
                oldValues: null,
                newValues: ['user_message' => $userMessage],
                actorType: 'ai',
                actorName: 'ClinicalFirewall'
            );

            return $safetyResponse;
        }

        // 3. Montar o Prompt do Sistema com Regras e Memória Isolada
        $repName = $representative?->name ?? 'Emmanuel Marcarini';
        $clientName = $contact?->name ?? 'Cliente';
        $companyName = $company?->trade_name ?? $company?->name ?? 'Empresa';

        $memories = AiMemory::where('contact_id', $contact?->id)
            ->where('is_confirmed', true)
            ->pluck('content')
            ->implode("\n- ");

        $systemPrompt = <<<PROMPT
Você é o Assistente Comercial Inteligente do representante {$repName}, alimentado por Google Gemini.
Você está atendendo o cliente {$clientName} da empresa {$companyName} pelo WhatsApp.

DIRETRIZES DE PERSONALIDADE E ESTILO:
- Tom: informal, cordial, prestativo, objetivo e brasileiro (ex: "opa, tudo bem?", "já vejo isso pra você", "perfeito, amigo").
- Respostas curtas e diretas, adequadas ao WhatsApp. Evite blocos gigantescos de texto.
- TRANSPARÊNCIA: Se o cliente perguntar se você é uma IA, robô ou assistente virtual, responda de forma natural e amigável que você é o assistente virtual do {$repName}, preparado para ajudar em cotações e pedidos rápidos. Não minta sobre ser uma pessoa humana física.

REGRAS COMERCIAIS ABSOLUTAS:
1. NUNCA INVENTE PREÇOS, DESCONTOS OU ESTOQUE. Todos os valores devem vir obrigatoriamente da ferramenta 'consultar_preco' ou 'buscar_produto'.
2. Se o cliente pedir quantidade (ex: "quanto fica 50 caixas de Dipirona?"), SEMPRE consulte o preço pela ferramenta 'consultar_preco' informando a quantidade correta para aplicar a melhor faixa ou campanha.
3. Se o cliente concordar com a compra e quiser fechar, gere a cotação com 'criar_cotacao' e em seguida finalize o pedido com 'criar_pedido'.
4. Se o cliente pedir para falar com uma pessoa, negociar prazos especiais fora do padrão ou expressar insatisfação grave, use a ferramenta 'transferir_para_humano'.
5. NUNCA dê conselhos médicos, posologia ou prescrições clínicas.

MEMÓRIA HISTÓRICA DO CLIENTE:
- {$memories}
PROMPT;

        // 4. Execução da IA com Google Gemini
        $responseContent = $this->callGeminiAiOrFallback($conversation, $systemPrompt, $userMessage);

        if (!empty($responseContent)) {
            $this->saveAndSendMessage($conversation, $responseContent, $instanceName);
        }

        return $responseContent;
    }

    /**
     * Execução com Google Gemini API ou Fallback Determinístico Local.
     */
    protected function callGeminiAiOrFallback(Conversation $conversation, string $systemPrompt, string $userMessage): string
    {
        if (!$this->geminiService->isConfigured() || config('app.env') === 'testing') {
            return $this->executeDeterministicFallback($conversation, $userMessage);
        }

        try {
            // Carregar últimas 6 mensagens para contexto de conversa no formato do Gemini
            $recentMessages = $conversation->messages()
                ->latest()
                ->take(6)
                ->get()
                ->reverse();

            $contents = [];
            foreach ($recentMessages as $msg) {
                $contents[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'model',
                    'parts' => [
                        ['text' => $msg->content],
                    ],
                ];
            }

            // Mensagem atual do usuário
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $userMessage],
                ],
            ];

            $tools = $this->tools->getToolDefinitions('customer');

            $result = $this->geminiService->generateContent(
                contents: $contents,
                systemInstruction: $systemPrompt,
                tools: $tools,
                temperature: 0.3
            );

            if (!$result['success']) {
                Log::warning("Gemini retornou erro, usando fallback: " . ($result['error'] ?? 'desconhecido'));
                return $this->executeDeterministicFallback($conversation, $userMessage);
            }

            $candidate = $result['data']['candidates'][0] ?? null;
            if (!$candidate) {
                return $this->executeDeterministicFallback($conversation, $userMessage);
            }

            $parts = $candidate['content']['parts'] ?? [];
            $functionCalls = [];
            $textResponse = '';

            foreach ($parts as $part) {
                if (!empty($part['functionCall'])) {
                    $functionCalls[] = $part['functionCall'];
                }
                if (!empty($part['text'])) {
                    $textResponse .= $part['text'];
                }
            }

            // Se o Gemini executou Function Calls
            if (!empty($functionCalls)) {
                // Adiciona o turno do modelo aos contents
                $contents[] = [
                    'role' => 'model',
                    'parts' => $parts,
                ];

                $toolResponses = [];
                foreach ($functionCalls as $fnCall) {
                    $toolName = $fnCall['name'];
                    $toolArgs = $fnCall['args'] ?? [];

                    $toolResult = $this->tools->executeTool(
                        toolName: $toolName,
                        arguments: $toolArgs,
                        contact: $conversation->contact,
                        company: $conversation->contact?->company,
                        conversation: $conversation
                    );

                    $toolResponses[] = [
                        'functionResponse' => [
                            'name' => $toolName,
                            'response' => [
                                'output' => $toolResult,
                            ],
                        ],
                    ];
                }

                // Adiciona as respostas das ferramentas
                $contents[] = [
                    'role' => 'function',
                    'parts' => $toolResponses,
                ];

                // Segunda chamada para o Gemini gerar a resposta final ao cliente
                $secondResult = $this->geminiService->generateContent(
                    contents: $contents,
                    systemInstruction: $systemPrompt,
                    temperature: 0.3
                );

                if ($secondResult['success']) {
                    $secondParts = $secondResult['data']['candidates'][0]['content']['parts'] ?? [];
                    $finalText = '';
                    foreach ($secondParts as $sp) {
                        if (!empty($sp['text'])) {
                            $finalText .= $sp['text'];
                        }
                    }
                    if (!empty($finalText)) {
                        return trim($finalText);
                    }
                }
            }

            if (!empty($textResponse)) {
                return trim($textResponse);
            }

            return $this->executeDeterministicFallback($conversation, $userMessage);
        } catch (\Throwable $e) {
            Log::error("Erro na chamada Gemini API: " . $e->getMessage());
            return $this->executeDeterministicFallback($conversation, $userMessage);
        }
    }

    /**
     * Fallback determinístico inteligente para execução sem chave ou em testes automatizados.
     */
    public function executeDeterministicFallback(Conversation $conversation, string $userMessage): string
    {
        $contact = $conversation->contact;
        $company = $contact?->company;
        $lower = mb_strtolower($userMessage, 'UTF-8');

        // Intenção 1: Pergunta sobre se é robô / IA
        if (str_contains($lower, 'robô') || str_contains($lower, 'humano') || str_contains($lower, 'inteligência artificial') || str_contains($lower, 'você é ia')) {
            return "Opa, sou o assistente digital do {$conversation->representative?->name}! Estou aqui para te passar preços e condições rapidinho no WhatsApp.";
        }

        // Intenção 2: Transferência humana
        if (str_contains($lower, 'falar com humano') || str_contains($lower, 'atendente') || str_contains($lower, 'vendedor') || str_contains($lower, 'humano')) {
            $this->tools->executeTool('transferir_para_humano', ['motivo' => 'Solicitação do cliente'], $contact, $company, $conversation);
            return "Com certeza! Já avisei o nosso representante e ele vai assumir o contato por aqui em instantes.";
        }

        // Intenção 3: Consulta de preço ou produto
        if (str_contains($lower, 'quanto') || str_contains($lower, 'preço') || str_contains($lower, 'valor') || str_contains($lower, 'cotação') || str_contains($lower, 'dipirona') || str_contains($lower, 'amoxicilina') || str_contains($lower, 'paracetamol') || str_contains($lower, 'omeprazol') || str_contains($lower, 'losartana')) {
            preg_match('/\b(\d+)\b/', $userMessage, $matches);
            $qty = isset($matches[1]) ? (int) $matches[1] : 1;
            if ($qty <= 0) $qty = 1;

            $term = 'Dipirona';
            if (str_contains($lower, 'amox')) $term = 'Amoxicilina';
            elseif (str_contains($lower, 'omep')) $term = 'Omeprazol';
            elseif (str_contains($lower, 'losart')) $term = 'Losartana';
            elseif (str_contains($lower, 'paracet')) $term = 'Paracetamol';

            $search = $this->tools->executeTool('buscar_produto', ['termo' => $term]);
            if (!empty($search['products'])) {
                $prod = $search['products'][0];
                $priceRes = $this->tools->executeTool('consultar_preco', [
                    'produto_id' => $prod['id'],
                    'quantidade' => $qty,
                ], $contact, $company);

                $unitFormatted = number_format($priceRes['final_unit_price'], 2, ',', '.');
                $totalFormatted = number_format($priceRes['total_amount'], 2, ',', '.');

                $reply = "Opa! Para {$qty} un de {$prod['nome']}, o valor sai por R$ {$unitFormatted} a unidade (Total: R$ {$totalFormatted}).";
                if ($priceRes['condition_source'] === 'campaign') {
                    $reply .= " (Condição especial de Campanha aplicada!)";
                } elseif ($priceRes['condition_source'] === 'tier_price') {
                    $reply .= " (Desconto por faixa de quantidade aplicado!)";
                }
                return $reply;
            }
        }

        return "Opa, tudo bem? Consigo consultar preços atualizados, promoções por quantidade e montar cotações rápidas para você. Como posso te ajudar hoje?";
    }

    protected function saveAndSendMessage(Conversation $conversation, string $content, ?string $instanceName = null): void
    {
        $conversation->messages()->create([
            'direction' => 'outbound',
            'sender_type' => 'ai',
            'content' => $content,
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($conversation->contact?->phone) {
            try {
                $instance = $instanceName 
                    ?? $conversation->representative?->getEffectiveWhatsAppInstance() 
                    ?? $conversation->contact?->representative?->getEffectiveWhatsAppInstance()
                    ?? config('services.evolution.instance');
                
                if (!empty($instance)) {
                    $this->whatsappService->setInstance($instance);
                    $this->whatsappService->sendTextMessage($conversation->contact->phone, $content);
                } else {
                    Log::warning("Instância WhatsApp não identificada para envio na conversa #{$conversation->id}");
                }
            } catch (\Throwable $e) {
                Log::error("Erro ao enviar mensagem WhatsApp pelo agente de IA: " . $e->getMessage(), [
                    'phone' => $conversation->contact->phone,
                    'instance' => $instance ?? 'unknown',
                    'exception' => $e,
                ]);
            }
        }
    }
}
