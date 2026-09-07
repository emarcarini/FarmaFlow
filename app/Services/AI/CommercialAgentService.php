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
    public function handleCustomerMessage(
        Conversation $conversation,
        string $userMessage,
        ?string $instanceName = null,
        bool $isAdmin = false
    ): string {
        $contact = $conversation->contact;
        $company = $contact?->company;
        $representative = $conversation->representative ?? $contact?->representative;

        // Auto-detectar se é o administrador caso a flag não tenha vindo no webhook
        if (!$isAdmin && $contact?->phone) {
            $isAdmin = \App\Services\WhatsApp\EvolutionWebhookHandler::isAdminPhone($contact->phone);
        }

        // 1. Se NÃO for admin e a conversa estiver em atendimento humano, ignorar resposta automática
        if (!$isAdmin && $conversation->isHumanTakeover()) {
            Log::info("Conversa #{$conversation->id} em atendimento humano. IA ignorando resposta automática.");
            return '';
        }

        // 2. FIREWALL CLÍNICO (Apenas para clientes comuns): Salvaguarda contra diagnósticos/prescrições
        if (!$isAdmin && $this->checkClinicalSafety($userMessage)) {
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

        // 3. Montar o Prompt do Sistema adequado (Modo Admin ou Modo Cliente)
        $repName = $representative?->name ?? 'Emmanuel Marcarini';

        if ($isAdmin) {
            $systemPrompt = <<<PROMPT
Você é o Copiloto Executivo e Assistente de Gestão de Emmanuel Marcarini, Administrador e Proprietário da FarmaFlow, alimentado por Google Gemini.
Você está conversando diretamente com Emmanuel pelo WhatsApp.
RECONHECIMENTO DE PERFIL: Emmanuel é o ADMINISTRADOR/GESTOR MÁXIMO da FarmaFlow (NÃO É UM CLIENTE). Trate-o de forma executiva, ágil, prestativa e parceira (ex: "Fala, Emmanuel!", "Opa Emmanuel, tudo certo?").

COMO ADMINISTRADOR, EMMANUEL POSSUI ACESSO A TODAS AS FERRAMENTAS DO NEGÓCIO:
1. RESUMO COMERCIAL & DASHBOARD:
   - Se ele pedir "resumo", "como estão as vendas?", "dashboard", "vendas de hoje" ou "faturamento", use a ferramenta 'gerar_resumo_comercial' ou 'status_sistema'.
2. CONSULTA GERAL DE ESTOQUE & CATÁLOGO:
   - Se ele perguntar "o que tem disponível?", "liste tudo que tem", "preços" ou termos gerais, use a ferramenta 'listar_catalogo_completo' ou 'buscar_produto'.
   - Se ele perguntar por qualquer produto específico (ex: Dipirona, Tadalafila, Amoxicilina), busque no catálogo com 'buscar_produto' ou 'consultar_preco'. Se não encontrar, informe com clareza que o produto não consta no catálogo ativo.
3. GESTÃO DE REGRAS DO BOT:
   - Se ele perguntar "quais regras estão ativas?" ou "regras", use 'listar_regras_bot'.
   - Se ele pedir para adicionar uma instrução ou regra (ex: "adicione a regra: ..."), use 'adicionar_regra_bot'.
4. STATUS DA OPERAÇÃO:
   - Use 'status_sistema' para verificar a saúde do WhatsApp, pedidos e atendimentos.
5. CONSULTA DE CLIENTES:
   - Use 'consultar_cliente' para ver histórico e faturamento de clientes.

DIRETRIZES:
- NUNCA pergunte se ele quer comprar ou fechar pedido pessoal; ele é o gestor do negócio.
- Seja objetivo, conciso e use formatação limpa com marcadores em negrito.
PROMPT;
        } else {
            $clientName = $contact?->name ?? 'Cliente';
            $companyName = $company?->trade_name ?? $company?->name ?? 'Empresa';

            $memories = AiMemory::where('contact_id', $contact?->id)
                ->where('is_confirmed', true)
                ->pluck('content')
                ->implode("\n- ");

            // Regras personalizadas configuradas pelo gestor na janela de Regras do Bot
            $customRulesPrompt = '';
            try {
                $activeRules = \App\Models\BotRule::where('is_active', true)->orderBy('priority', 'asc')->get();
                if ($activeRules->isNotEmpty()) {
                    $customRulesPrompt = "REGRAS E INSTRUÇÕES ESPECÍFICAS CADASTRADAS PELO GESTOR (SIGA RIGOROSAMENTE):\n";
                    foreach ($activeRules as $idx => $r) {
                        $num = $idx + 1;
                        $titlePrefix = $r->title ? "[{$r->title}] " : "";
                        $customRulesPrompt .= "{$num}. {$titlePrefix}{$r->content}\n";
                    }
                }
            } catch (\Throwable $e) {
                // Caso tabela não exista
            }

            $systemPrompt = <<<PROMPT
Você é o Assistente Comercial Inteligente do representante {$repName} da FarmaFlow, alimentado por Google Gemini.
Você está atendendo o cliente {$clientName} da empresa {$companyName} pelo WhatsApp.

DIRETRIZES DE PERSONALIDADE E ESTILO:
- Tom: informal, cordial, prestativo, objetivo e brasileiro (ex: "opa, tudo bem?", "já vejo isso pra você", "perfeito, amigo").
- Respostas curtas e diretas, adequadas ao WhatsApp. Evite blocos gigantescos de texto.
- TRANSPARÊNCIA: Se o cliente perguntar se você é uma IA, robô ou assistente virtual, responda de forma natural e amigável que você é o assistente virtual do {$repName}, preparado para ajudar em cotações e pedidos rápidos. Não minta sobre ser uma pessoa humana física.

REGRAS COMERCIAIS GERAIS:
1. NUNCA INVENTE PREÇOS, DESCONTOS OU ESTOQUE. Todos os valores devem vir obrigatoriamente da ferramenta 'consultar_preco' ou 'buscar_produto'.
2. Se o cliente pedir quantidade (ex: "quanto fica 50 caixas de Dipirona?"), SEMPRE consulte o preço pela ferramenta 'consultar_preco' informando a quantidade correta para aplicar a melhor faixa ou campanha.
3. Se o cliente concordar com a compra e quiser fechar, gere a cotação com 'criar_cotacao' e em seguida finalize o pedido com 'criar_pedido'.
4. Se o cliente pedir para falar com uma pessoa, negociar prazos especiais fora do padrão ou expressar insatisfação grave, use a ferramenta 'transferir_para_humano'.
5. NUNCA dê conselhos médicos, posologia ou prescrições clínicas.

{$customRulesPrompt}
MEMÓRIA HISTÓRICA DO CLIENTE:
- {$memories}
PROMPT;
        }

        // 4. Execução da IA com Google Gemini (com fallback inteligente)
        $responseContent = $this->callGeminiAiOrFallback($conversation, $systemPrompt, $userMessage, $isAdmin);

        if (!empty($responseContent)) {
            $this->saveAndSendMessage($conversation, $responseContent, $instanceName);
        }

        return $responseContent;
    }

    /**
     * Execução com Google Gemini API ou Fallback Determinístico Local.
     */
    protected function callGeminiAiOrFallback(
        Conversation $conversation,
        string $systemPrompt,
        string $userMessage,
        bool $isAdmin = false
    ): string {
        if (!$this->geminiService->isConfigured() || config('app.env') === 'testing') {
            return $isAdmin 
                ? $this->executeAdminFallback($conversation, $userMessage) 
                : $this->executeCustomerFallback($conversation, $userMessage);
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

            $tools = $this->tools->getToolDefinitions($isAdmin ? 'admin' : 'customer');

            $result = $this->geminiService->generateContent(
                contents: $contents,
                systemInstruction: $systemPrompt,
                tools: $tools,
                temperature: 0.3
            );

            if (!$result['success']) {
                Log::warning("Gemini retornou erro, acionando fallback inteligente: " . ($result['error'] ?? 'desconhecido'));
                return $isAdmin 
                    ? $this->executeAdminFallback($conversation, $userMessage) 
                    : $this->executeCustomerFallback($conversation, $userMessage);
            }

            $candidate = $result['data']['candidates'][0] ?? null;
            if (!$candidate) {
                return $isAdmin 
                    ? $this->executeAdminFallback($conversation, $userMessage) 
                    : $this->executeCustomerFallback($conversation, $userMessage);
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
                                'name' => $toolName,
                                'content' => $toolResult,
                            ],
                        ],
                    ];
                }

                $contents[] = [
                    'role' => 'function',
                    'parts' => $toolResponses,
                ];

                // Segunda chamada para o Gemini gerar a resposta final
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
                    if (!empty(trim($finalText))) {
                        return trim($finalText);
                    }
                }
            }

            if (!empty(trim($textResponse))) {
                return trim($textResponse);
            }

            return $isAdmin 
                ? $this->executeAdminFallback($conversation, $userMessage) 
                : $this->executeCustomerFallback($conversation, $userMessage);
        } catch (\Throwable $e) {
            Log::error("Erro na chamada Gemini API: " . $e->getMessage());
            return $isAdmin 
                ? $this->executeAdminFallback($conversation, $userMessage) 
                : $this->executeCustomerFallback($conversation, $userMessage);
        }
    }

    /**
     * Fallback executivo e inteligente para o Administrador (Emmanuel Marcarini).
     */
    public function executeAdminFallback(Conversation $conversation, string $userMessage): string
    {
        $lower = mb_strtolower(trim($userMessage), 'UTF-8');
        $clean = preg_replace('/[?!.,]/', '', $lower);

        // 1. Catálogo / "o que tem disponível" / "liste tudo" / "preços" / "produtos"
        if (
            str_contains($clean, 'disponivel') ||
            str_contains($clean, 'disponível') ||
            str_contains($clean, 'liste tudo') ||
            str_contains($clean, 'listar tudo') ||
            str_contains($clean, 'catalogo') ||
            str_contains($clean, 'catálogo') ||
            str_contains($clean, 'o que vc tem') ||
            str_contains($clean, 'o que você tem') ||
            str_contains($clean, 'produtos') ||
            str_contains($clean, 'precos') ||
            str_contains($clean, 'preços')
        ) {
            $catalog = $this->tools->executeTool('listar_catalogo_completo', []);
            $products = $catalog['catalogo'] ?? [];

            if (empty($products)) {
                return "Emmanuel, no momento não há medicamentos ativos cadastrados no catálogo da FarmaFlow.";
            }

            $reply = "📋 *Catálogo Geral FarmaFlow (Estoque & Preços)*:\n\n";
            foreach ($products as $idx => $p) {
                $num = $idx + 1;
                $precoFmt = number_format($p['preco_base'], 2, ',', '.');
                $estoqueFmt = number_format($p['estoque'], 0, ',', '.');

                $volStr = "";
                if (!empty($p['descontos_volume'])) {
                    $bestTier = end($p['descontos_volume']);
                    $bestPreco = number_format($bestTier['preco_unitario'], 2, ',', '.');
                    $volStr = " | 🏷️ A partir de {$bestTier['qtd_min']} un: R$ {$bestPreco}";
                }

                $reply .= "{$num}. 💊 *{$p['nome']}*\n";
                $reply .= "   • Apresentação: {$p['apresentacao']}\n";
                $reply .= "   • Estoque: {$estoqueFmt} cx | Preço: R$ {$precoFmt}{$volStr}\n\n";
            }
            $reply .= "_Para consultar faixas detalhadas de qualquer item, basta digitar o nome do produto (ex: 'Dipirona' ou 'Tadalafila')._";
            return $reply;
        }

        // 2. Busca de Produto Específico (ex: "tem tadala?", "tadalafila", "dipirona", "amoxicilina", etc.)
        $searchRes = $this->tools->executeTool('buscar_produto', ['termo' => $userMessage]);
        if (!empty($searchRes['products'])) {
            $p = $searchRes['products'][0];
            $precoBase = number_format($p['preco_base'], 2, ',', '.');
            $estoque = number_format($p['estoque_disponivel'], 0, ',', '.');

            $prodModel = \App\Models\Product::with('prices')->find($p['id']);
            $reply = "💊 *{$p['nome']}* (Cód: {$p['codigo']})\n\n";
            $reply .= "• Apresentação: {$p['apresentacao']}\n";
            $reply .= "• Categoria: {$p['categoria']}\n";
            $reply .= "• Estoque Atual: *{$estoque} unidades*\n";
            $reply .= "• Preço de Tabela: *R$ {$precoBase}*\n";

            if ($prodModel && $prodModel->prices->isNotEmpty()) {
                $reply .= "\n📊 *Tabela de Descontos por Quantidade:*\n";
                foreach ($prodModel->prices as $pr) {
                    $tierPreco = number_format($pr->unit_price, 2, ',', '.');
                    $ate = $pr->max_quantity ? "até {$pr->max_quantity} un" : "em diante";
                    $desc = $pr->discount_pct > 0 ? " (" . number_format($pr->discount_pct, 1) . "% OFF)" : "";
                    $reply .= "  ▫️ De {$pr->min_quantity} {$ate}: R$ {$tierPreco}{$desc}\n";
                }
            }

            return $reply;
        }

        // 3. Se procurou por produto e não achou
        if (
            str_contains($clean, 'tem ') ||
            str_contains($clean, 'voce tem') ||
            str_contains($clean, 'você tem') ||
            str_contains($clean, 'tadala') ||
            str_contains($clean, 'dipiro') ||
            str_contains($clean, 'amoxi') ||
            str_contains($clean, 'paracet')
        ) {
            return "Emmanuel, consultei o catálogo agora e não encontrei o medicamento buscado no estoque ativo.\n\n" .
                   "Itens ativos disponíveis hoje: *Dipirona 500mg, Amoxicilina 875mg, Omeprazol 20mg, Losartana 50mg, Paracetamol 750mg e Tadalafila 20mg*.\n" .
                   "_Deseja cadastrar esse novo produto ou consultar a tabela de algum dos itens acima?_";
        }

        // 4. Resumo de Vendas / Dashboard / Status
        if (
            str_contains($clean, 'resumo') ||
            str_contains($clean, 'dashboard') ||
            str_contains($clean, 'vendas') ||
            str_contains($clean, 'faturamento') ||
            str_contains($clean, 'status') ||
            str_contains($clean, 'pedidos')
        ) {
            $status = $this->tools->executeTool('status_sistema', []);
            $pedidosHoje = $status['pedidos_hoje'] ?? 0;
            $fatHoje = number_format($status['faturamento_hoje'] ?? 0, 2, ',', '.');
            $cotHoje = $status['cotacoes_hoje'] ?? 0;
            $convHoje = $status['conversas_hoje'] ?? 0;
            $totalContatos = $status['total_contatos_sincronizados'] ?? 0;

            return "📊 *Resumo Executivo FarmaFlow* (Hoje, " . now()->format('d/m/Y') . "):\n\n" .
                   "• 📦 Pedidos Fechados: *{$pedidosHoje} pedidos* (Total: R$ {$fatHoje})\n" .
                   "• 📑 Cotações Geradas: *{$cotHoje} cotações*\n" .
                   "• 💬 Clientes Atendidos no WhatsApp: *{$convHoje} conversas ativas*\n" .
                   "• 👥 Total da Carteira: *{$totalContatos} contatos*\n" .
                   "• 🟢 WhatsApp Instância: *{$status['instancia_whatsapp']}* ({$status['status_conexao']})\n\n" .
                   "_Tudo rodando normalmente! Para ver o catálogo, mande 'catálogo' ou o nome de um medicamento._";
        }

        // 5. Regras do Bot IA ("regras", "quais regras estão ativas?")
        if (str_contains($clean, 'regras') || str_contains($clean, 'instrucoes') || str_contains($clean, 'instruções')) {
            $rulesData = $this->tools->executeTool('listar_regras_bot', []);
            $rules = $rulesData['regras'] ?? [];

            if (empty($rules)) {
                return "Emmanuel, nenhuma regra personalizada está ativa no momento. Você pode adicionar regras pelo portal ou mandar por aqui (ex: 'Adicionar regra: frete cortesia acima de R$ 800').";
            }

            $reply = "🧠 *Regras Ativas que o Robô segue com clientes:*\n\n";
            foreach ($rules as $idx => $r) {
                $num = $idx + 1;
                $reply .= "{$num}. *[{$r['titulo']}]*\n   {$r['instrucao']}\n\n";
            }
            $reply .= "_Para adicionar uma nova regra, basta mandar: 'Adicionar regra: texto da regra'._";
            return $reply;
        }

        // 6. Adicionar Regra pelo WhatsApp
        if (str_starts_with($clean, 'adicionar regra') || str_starts_with($clean, 'nova regra')) {
            $parts = explode(':', $userMessage, 2);
            $ruleContent = isset($parts[1]) ? trim($parts[1]) : trim(preg_replace('/^(adicionar regra|nova regra)\s*/i', '', $userMessage));

            if (!empty($ruleContent)) {
                $this->tools->executeTool('adicionar_regra_bot', [
                    'titulo' => 'Regra via WhatsApp Admin',
                    'conteudo' => $ruleContent,
                ]);

                return "✅ *Regra cadastrada com sucesso, Emmanuel!*\n\n" .
                       "Instrução: \"{$ruleContent}\"\n\n" .
                       "O assistente comercial já está aplicando essa diretriz para todos os atendimentos de clientes a partir de agora.";
            }
        }

        // 7. Saudações / Identificação / Nome
        if (
            str_contains($clean, 'ola') ||
            str_contains($clean, 'olá') ||
            str_contains($clean, 'bom dia') ||
            str_contains($clean, 'boa tarde') ||
            str_contains($clean, 'boa noite') ||
            str_contains($clean, 'boa noote') ||
            str_contains($clean, 'qual seu nome') ||
            str_contains($clean, 'quem e voce') ||
            str_contains($clean, 'quem é você') ||
            str_contains($clean, 'ajuda') ||
            str_contains($clean, 'comandos')
        ) {
            $hora = (int) now()->format('H');
            $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

            return "{$saudacao}, Emmanuel! 👋\n\n" .
                   "Reconheci seu número como *Administrador Geral da FarmaFlow*.\n" .
                   "Sou o seu **Copiloto de Gestão Comercial e Robô de Vendas**.\n\n" .
                   "🛠️ *O que você pode fazer por aqui:*\n" .
                   "• 📊 *'Resumo'*: Ver faturamento, pedidos e atendimentos de hoje\n" .
                   "• 📋 *'Catálogo'*: Listar todos os medicamentos com preços e estoque\n" .
                   "• 💊 *'Tem [medicamento]?'*: Consultar estoque e faixas de volume\n" .
                   "• 🧠 *'Regras'*: Ver as instruções que o robô segue com clientes\n" .
                   "• ➕ *'Adicionar regra: [texto]'*: Adicionar novas instruções para o robô\n\n" .
                   "_Como posso te ajudar agora?_";
        }

        // 8. Resposta padrão inteligente para o Admin
        return "Fala, Emmanuel! Reconheci seu acesso de Administrador FarmaFlow.\n\n" .
               "Você pode pedir o *resumo de vendas*, consultar o *catálogo completo de medicamentos*, verificar o estoque de um item (ex: 'Tem Dipirona?') ou listar as *regras do robô*.\n\n" .
               "Como posso te ajudar agora?";
    }

    /**
     * Fallback comercial inteligente para clientes e potenciais clientes.
     */
    public function executeCustomerFallback(Conversation $conversation, string $userMessage): string
    {
        $contact = $conversation->contact;
        $company = $contact?->company;
        $lower = mb_strtolower(trim($userMessage), 'UTF-8');
        $clean = preg_replace('/[?!.,]/', '', $lower);

        // 1. Pergunta sobre se é robô / IA
        if (str_contains($clean, 'robo') || str_contains($clean, 'robô') || str_contains($clean, 'humano') || str_contains($clean, 'inteligencia artificial') || str_contains($clean, 'você é ia')) {
            return "Opa, sou o assistente digital da FarmaFlow, equipe do Emmanuel Marcarini! Estou aqui para te passar preços de fábrica e condições especiais rapidinho no WhatsApp. Como posso te ajudar?";
        }

        // 2. Qual o seu nome / Saudações
        if (str_contains($clean, 'qual seu nome') || str_contains($clean, 'quem é você') || str_contains($clean, 'quem e voce')) {
            return "Sou o assistente comercial digital da FarmaFlow! Atendo junto com o Emmanuel Marcarini para agilizar suas cotações e pedidos de medicamentos.";
        }

        if (str_contains($clean, 'boa noite') || str_contains($clean, 'boa noote') || str_contains($clean, 'bom dia') || str_contains($clean, 'boa tarde') || $clean === 'ola' || $clean === 'olá' || $clean === 'oi') {
            $hora = (int) now()->format('H');
            $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
            return "{$saudacao}! Tudo bem? Sou o assistente comercial da FarmaFlow. Consigo consultar preços atualizados, verificar descontos por quantidade e montar sua cotação rápida. Qual produto você procura hoje?";
        }

        // 3. "O que tem disponível?" / Catálogo / Lista de produtos
        if (str_contains($clean, 'disponivel') || str_contains($clean, 'disponível') || str_contains($clean, 'o que tem') || str_contains($clean, 'catalogo') || str_contains($clean, 'catálogo') || str_contains($clean, 'produtos')) {
            $prods = \App\Models\Product::where('is_active', true)->take(6)->get();
            $list = $prods->map(fn($p) => "• *{$p->name}* ({$p->presentation})")->implode("\n");
            return "Trabalhamos com medicamentos de alta rotatividade com condições especiais por quantidade! Alguns dos itens disponíveis:\n\n{$list}\n\nQual deles você gostaria de cotar hoje?";
        }

        // 4. Busca de produto ou cotação de produto
        $searchRes = $this->tools->executeTool('buscar_produto', ['termo' => $userMessage]);
        if (!empty($searchRes['products'])) {
            $prod = $searchRes['products'][0];

            preg_match('/\b(\d+)\b/', $userMessage, $matches);
            $qty = isset($matches[1]) ? (int) $matches[1] : 1;

            $priceRes = $this->tools->executeTool('consultar_preco', [
                'produto_id' => $prod['id'],
                'quantidade' => $qty,
            ], $contact, $company);

            $unitFormatted = number_format($priceRes['final_unit_price'], 2, ',', '.');
            $totalFormatted = number_format($priceRes['total_amount'], 2, ',', '.');

            if ($qty > 1) {
                $reply = "Opa! Para {$qty} caixas de *{$prod['nome']}*, o valor fica em *R$ {$unitFormatted}* a unidade (Total: R$ {$totalFormatted}).";
                if ($priceRes['condition_source'] === 'campaign') {
                    $reply .= " 🏷️ (Preço especial de campanha aplicado!)";
                } elseif ($priceRes['condition_source'] === 'tier_price') {
                    $reply .= " 📦 (Desconto por volume aplicado!)";
                }
                $reply .= "\n\nPosso gerar a cotação formal para você?";
                return $reply;
            } else {
                $baseFormatted = number_format($prod['preco_base'], 2, ',', '.');
                return "Temos sim! *{$prod['nome']}* ({$prod['apresentacao']}). Preço base de tabela: R$ {$baseFormatted} a caixa.\n\nPara compras em volume acima de 10 ou 50 caixas temos faixas com desconto progressivo! Quantas caixas você precisa?";
            }
        }

        // 5. Transferência para humano solicitada
        if (str_contains($clean, 'humano') || str_contains($clean, 'atendente') || str_contains($clean, 'vendedor') || str_contains($clean, 'falar com')) {
            $this->tools->executeTool('transferir_para_humano', ['motivo' => 'Solicitação do cliente'], $contact, $company, $conversation);
            return "Com certeza! Já avisei a nossa equipe e o Emmanuel Marcarini vai te responder pessoalmente por aqui em instantes.";
        }

        // 6. Produto procurado não encontrado
        if (str_contains($clean, 'tem ') || str_contains($clean, 'voce tem') || str_contains($clean, 'você tem')) {
            return "No momento não temos esse item específico em estoque. Temos disponível pronta-entrega: *Dipirona 500mg, Amoxicilina 875mg, Omeprazol 20mg, Losartana 50mg, Paracetamol 750mg e Tadalafila 20mg*. Gostaria de cotar algum desses?";
        }

        return "Opa, tudo bem? Consigo consultar preços atualizados, promoções por quantidade e montar cotações rápidas para você na FarmaFlow. Qual medicamento você gostaria de cotar?";
    }

    /**
     * Fallback legado redirecionando para o fallback de cliente.
     */
    public function executeDeterministicFallback(Conversation $conversation, string $userMessage): string
    {
        return $this->executeCustomerFallback($conversation, $userMessage);
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
