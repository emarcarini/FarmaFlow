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
            $isAuthorized = $contact && !empty($contact->company_id) && $contact->isAuthorizedForCompany();
            $authorizedCompany = $isAuthorized ? $contact->company : null;

            $memories = AiMemory::where('contact_id', $contact?->id)
                ->where('is_confirmed', true)
                ->pluck('content')
                ->implode("\n- ");

            // Regras personalizadas configuradas pelo gestor na janela de Regras do Bot
            $customRulesPrompt = '';
            try {
                $activeRules = \App\Models\BotRule::where('is_active', true)->orderBy('priority', 'asc')->get();
                if ($activeRules->isNotEmpty()) {
                    $customRulesPrompt = "REGRAS E DIRETRIZES DE ATENDIMENTO ATIVAS (SIGA RIGOROSAMENTE):\n";
                    foreach ($activeRules as $idx => $r) {
                        $num = $idx + 1;
                        $titlePrefix = $r->title ? "[{$r->title}] " : "";
                        $customRulesPrompt .= "{$num}. {$titlePrefix}{$r->content}\n";
                    }
                }

                $prohibitedWords = \App\Models\BotSetting::get('prohibited_words', '');
                if (!empty($prohibitedWords)) {
                    $customRulesPrompt .= "\nTERMOS E PALAVRAS ESTRITAMENTE PROIBIDAS (NUNCA UTILIZE): {$prohibitedWords}\n";
                }

                $maxDiscount = \App\Models\BotSetting::get('max_autonomous_discount_pct', 5.0);
                $customRulesPrompt .= "\nLIMITE MÁXIMO DE DESCONTO AUTÔNOMO: {$maxDiscount}%. Acima disso, oriente o cliente a falar com Emmanuel Marcarini.\n";
            } catch (\Throwable $e) {
                // Caso tabela não exista
            }

            if ($isAuthorized && $authorizedCompany) {
                $clientName = $contact?->name ?? 'Cliente';
                $companyName = $authorizedCompany->trade_name ?? $authorizedCompany->name;
                $companyDoc = $authorizedCompany->document ?? 'Não informado';

                $systemPrompt = <<<PROMPT
Você é o Assistente Comercial Inteligente da FarmaFlow (equipe de Emmanuel Marcarini), alimentado por Google Gemini.
Você está atendendo o cliente {$clientName}, contato AUTORIZADO da empresa {$companyName} (CNPJ: {$companyDoc}) pelo WhatsApp.

DIRETRIZES DE PERSONALIDADE E ESTILO:
- Tom: cordial, prestativo, profissional e objetivo (ex: "opa, tudo bem?", "já vejo isso pra você", "perfeito!").
- Respostas curtas e diretas, adequadas ao WhatsApp. Evite blocos gigantescos de texto.
- TRANSPARÊNCIA: Se o cliente perguntar se você é uma IA, robô ou assistente virtual, responda de forma natural que você é o assistente virtual da FarmaFlow, preparado para ajudar em cotações e pedidos rápidos.

ISOLAMENTO ESTRITO POR CNPJ (REGRA DE SEGURANÇA MÁXIMA):
1. Este contato possui autorização EXCLUSIVAMENTE para tratar sobre a empresa {$companyName} (CNPJ: {$companyDoc}).
2. Você NUNCA deve fornecer, citar ou discutir informações, pedidos, histórico, faturamento ou preços de NENHUMA OUTRA EMPRESA ou cliente do CRM.
3. Se o cliente perguntar sobre outras farmácias, concorrentes ou clientes da carteira, recuse educadamente informando que os dados de cada cliente e CNPJ são estritamente sigilosos e confidenciais na FarmaFlow.
4. Para consultas de produtos, promoções e pedidos para a empresa {$companyName}, atenda prontamente utilizando as ferramentas disponíveis.

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
            } else {
                $clientPhone = $contact?->phone ?? '';

                $systemPrompt = <<<PROMPT
Você é o Assistente Comercial Inteligente da FarmaFlow (equipe de Emmanuel Marcarini), alimentado por Google Gemini.
Você está conversando pelo WhatsApp com o número {$clientPhone}.

STATUS DE AUTORIZAÇÃO: CONTATO NÃO AUTORIZADO / NÃO VINCULADO A NENHUM CNPJ NO CRM.
1. O telefone {$clientPhone} NÃO está cadastrado como telefone autorizado de nenhum cliente ou CNPJ ativo no sistema FarmaFlow.
2. Por segurança comercial, compliance regulatório e sigilo de dados:
   - Você NÃO deve liberar condições faturadas a prazo, dados de pedidos internos ou informações comerciais sigilosas para números não autorizados.
   - Você DEVE informar com cordialidade que, por segurança, os atendimentos da FarmaFlow são vinculados ao CNPJ da farmácia/drogaria e que este número ainda não consta como telefone autorizado no cadastro de clientes.
   - Solicite que o cliente informe o CNPJ da farmácia/drogaria para checagem cadastral no sistema.
   - Se o usuário informar o CNPJ (com ou sem pontuação), utilize IMEDIATAMENTE a ferramenta 'verificar_cnpj_autorizacao' passando o CNPJ informado.
   - Se a ferramenta retornar que a empresa existe mas o telefone não está autorizado, oriente-o a solicitar ao responsável da farmácia ou ao Emmanuel Marcarini (55 28 99943-9677) a inclusão do número na lista de telefones autorizados no CRM.
   - Se a ferramenta retornar que o CNPJ não existe, informe educadamente e oriente-o a iniciar o credenciamento com Emmanuel Marcarini (55 28 99943-9677).
3. Seja sempre prestativo, educado e seguro. NUNCA revele dados de outros clientes.

{$customRulesPrompt}
PROMPT;
            }
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
        $isAuthorized = $contact && !empty($contact->company_id) && $contact->isAuthorizedForCompany();
        $lower = mb_strtolower(trim($userMessage), 'UTF-8');
        $clean = preg_replace('/[?!.,]/', '', $lower);

        // 1. Extração de CNPJ da mensagem (caso o usuário tenha informado um CNPJ)
        if (preg_match('/\b\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}\b/', $userMessage, $cnpjMatches) || preg_match('/\b\d{14}\b/', $userMessage, $cnpjMatches)) {
            $cnpjToCheck = $cnpjMatches[0];
            $cnpjRes = $this->tools->executeTool('verificar_cnpj_autorizacao', ['cnpj' => $cnpjToCheck], $contact, $company, $conversation);
            return $cnpjRes['mensagem'] ?? "Verificação de CNPJ realizada com sucesso.";
        }

        // 2. Se o contato NÃO for autorizado para nenhum CNPJ:
        if (!$isAuthorized) {
            $phoneFmt = $contact?->phone ?? '';

            // Pergunta sobre ser robô / quem é você
            if (str_contains($clean, 'robo') || str_contains($clean, 'robô') || str_contains($clean, 'humano') || str_contains($clean, 'ia') || str_contains($clean, 'quem e voce') || str_contains($clean, 'quem é você')) {
                return "Olá! Sou o assistente comercial digital da FarmaFlow, equipe do Emmanuel Marcarini! Para podermos liberar cotações e faturamento, preciso verificar o cadastro da sua farmácia. Qual o CNPJ da sua empresa?";
            }

            // Pedido de atendimento humano
            if (str_contains($clean, 'humano') || str_contains($clean, 'atendente') || str_contains($clean, 'emmanuel') || str_contains($clean, 'falar com')) {
                $this->tools->executeTool('transferir_para_humano', ['motivo' => 'Contato não autorizado solicitou atendimento humano'], $contact, $company, $conversation);
                return "Perfeito! Já avisei a nossa equipe e o Emmanuel Marcarini (55 28 99943-9677) vai te responder pessoalmente por aqui para verificar seu cadastro e autorizar seu número.";
            }

            // Pergunta sobre produtos/preços/catálogo
            if (str_contains($clean, 'dipiro') || str_contains($clean, 'tadala') || str_contains($clean, 'amoxi') || str_contains($clean, 'preco') || str_contains($clean, 'preço') || str_contains($clean, 'valor') || str_contains($clean, 'catalogo') || str_contains($clean, 'catálogo') || str_contains($clean, 'tem ')) {
                return "Trabalhamos com uma linha completa de medicamentos para farmácias e drogarias com faturamento direto de distribuidora!\n\nPorém, para consultar tabela de preços, aplicar descontos de faturamento e gerar cotações, precisamos checar o cadastro da sua empresa no nosso CRM. O seu telefone ({$phoneFmt}) ainda não consta na lista de telefones autorizados de nenhum CNPJ.\n\nPor favor, *informe o CNPJ da sua farmácia* para checagem cadastral, ou solicite ao Emmanuel Marcarini (55 28 99943-9677) a liberação do seu número.";
            }

            // Saudação ou mensagem genérica
            $hora = (int) now()->format('H');
            $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
            return "{$saudacao}! Sou o assistente comercial da FarmaFlow.\n\nPor segurança e conformidade cadastral, nossos atendimentos comerciais e cotações são exclusivos para farmácias e drogarias com CNPJ ativo e telefones autorizados no CRM. O seu telefone ({$phoneFmt}) ainda não está vinculado a um CNPJ autorizado.\n\nPor favor, *informe o CNPJ da sua farmácia* para que eu possa verificar o seu cadastro, ou solicite ao gestor Emmanuel Marcarini (55 28 99943-9677) a inclusão do seu número na lista autorizada.";
        }

        // =========================================================================
        // CONTATO AUTORIZADO (Vinculado a um CNPJ ativo no CRM):
        // =========================================================================
        $companyName = $company->trade_name ?? $company->name;
        $companyDoc = $company->document ?? 'Não informado';

        // Regra de Isolamento estrito: se perguntar sobre outros clientes/concorrentes
        if (str_contains($clean, 'outro cliente') || str_contains($clean, 'outra farmacia') || str_contains($clean, 'outra farmácia') || str_contains($clean, 'concorrente') || str_contains($clean, 'outras empresas')) {
            return "Por política estrita de privacidade e conformidade da FarmaFlow, os dados de faturamento, pedidos e condições comerciais de cada CNPJ são estritamente sigilosos. Estou à disposição para atender com exclusividade a {$companyName} (CNPJ: {$companyDoc})! Como posso te ajudar hoje?";
        }

        // Pergunta sobre se é robô / IA
        if (str_contains($clean, 'robo') || str_contains($clean, 'robô') || str_contains($clean, 'humano') || str_contains($clean, 'inteligencia artificial') || str_contains($clean, 'você é ia')) {
            return "Opa, sou o assistente comercial digital da FarmaFlow! Atendo a equipe da {$companyName} para agilizar cotações, preços especiais e pedidos com entrega rápida. Como posso te ajudar?";
        }

        // Qual o seu nome / Saudações
        if (str_contains($clean, 'qual seu nome') || str_contains($clean, 'quem é você') || str_contains($clean, 'quem e voce')) {
            return "Sou o assistente comercial da FarmaFlow! Atendo exclusivamente a {$companyName} (CNPJ: {$companyDoc}) em cotações e pedidos de medicamentos.";
        }

        if (str_contains($clean, 'boa noite') || str_contains($clean, 'boa noote') || str_contains($clean, 'bom dia') || str_contains($clean, 'boa tarde') || $clean === 'ola' || $clean === 'olá' || $clean === 'oi') {
            $hora = (int) now()->format('H');
            $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
            return "{$saudacao}! Tudo bem? Sou o assistente comercial da FarmaFlow atendendo a *{$companyName}*. Consigo consultar preços atualizados, promoções por volume e gerar sua cotação rápida. Qual medicamento você precisa hoje?";
        }

        // "O que tem disponível?" / Catálogo / Lista de produtos
        if (str_contains($clean, 'disponivel') || str_contains($clean, 'disponível') || str_contains($clean, 'o que tem') || str_contains($clean, 'catalogo') || str_contains($clean, 'catálogo') || str_contains($clean, 'produtos')) {
            $prods = \App\Models\Product::where('is_active', true)->take(6)->get();
            $list = $prods->map(fn($p) => "• *{$p->name}* ({$p->presentation})")->implode("\n");
            return "Temos medicamentos de alta rotatividade com condições exclusivas para a *{$companyName}*! Alguns dos itens disponíveis:\n\n{$list}\n\nQual deles você gostaria de cotar hoje?";
        }

        // Busca de produto ou cotação de produto
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
                $reply = "Opa! Para {$qty} caixas de *{$prod['nome']}*, o valor especial para {$companyName} fica em *R$ {$unitFormatted}* a unidade (Total: R$ {$totalFormatted}).";
                if ($priceRes['condition_source'] === 'campaign') {
                    $reply .= " 🏷️ (Preço de campanha aplicado!)";
                } elseif ($priceRes['condition_source'] === 'tier_price') {
                    $reply .= " 📦 (Desconto por volume aplicado!)";
                }
                $reply .= "\n\nPosso gerar a cotação formal para o CNPJ {$companyDoc}?";
                return $reply;
            } else {
                $baseFormatted = number_format($prod['preco_base'], 2, ',', '.');
                return "Temos sim para a {$companyName}! *{$prod['nome']}* ({$prod['apresentacao']}). Preço base de tabela: R$ {$baseFormatted} a caixa.\n\nPara compras em volume acima de 10 ou 50 caixas temos descontos progressivos! Quantas caixas você precisa?";
            }
        }

        // Transferência para humano solicitada
        if (str_contains($clean, 'humano') || str_contains($clean, 'atendente') || str_contains($clean, 'vendedor') || str_contains($clean, 'falar com')) {
            $this->tools->executeTool('transferir_para_humano', ['motivo' => 'Solicitação do cliente'], $contact, $company, $conversation);
            return "Com certeza! Já notifiquei o Emmanuel Marcarini e ele entrará em contato com a {$companyName} por aqui em instantes.";
        }

        // Produto procurado não encontrado
        if (str_contains($clean, 'tem ') || str_contains($clean, 'voce tem') || str_contains($clean, 'você tem')) {
            return "No momento não temos esse item específico em estoque. Temos pronta-entrega para {$companyName}: *Dipirona 500mg, Amoxicilina 875mg, Omeprazol 20mg, Losartana 50mg, Paracetamol 750mg e Tadalafila 20mg*. Gostaria de cotar algum desses?";
        }

        return "Opa, tudo bem? Consigo consultar preços atualizados, promoções por quantidade e montar cotações rápidas para a *{$companyName}*. Qual medicamento você gostaria de cotar?";
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

                    // 1. Simulação Humana de Digitação com Delay Aleatório (padrão 5s a 30s)
                    $typingEnabled = \App\Models\BotSetting::get('typing_delay_enabled', true);
                    if ($typingEnabled && config('app.env') !== 'testing') {
                        @set_time_limit(120);
                        $minDelay = (int) \App\Models\BotSetting::get('typing_delay_min', 5);
                        $maxDelay = (int) \App\Models\BotSetting::get('typing_delay_max', 30);
                        if ($minDelay > $maxDelay) {
                            $minDelay = 5;
                            $maxDelay = 30;
                        }
                        $delaySeconds = rand($minDelay, $maxDelay);

                        // Ativa status "digitando..." no WhatsApp do cliente
                        $this->whatsappService->sendPresence($conversation->contact->phone, 'composing');

                        // Aguarda o período aleatório sorteado
                        sleep($delaySeconds);
                    }

                    // 2. Divisão de Mensagens Longas em Blocos Naturais
                    $splitEnabled = \App\Models\BotSetting::get('split_long_messages', true);
                    if ($splitEnabled && mb_strlen($content) > 350 && str_contains($content, "\n\n")) {
                        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
                        $chunks = [];
                        $currentChunk = '';
                        foreach ($paragraphs as $p) {
                            if (mb_strlen($currentChunk . "\n\n" . $p) > 350 && !empty($currentChunk)) {
                                $chunks[] = trim($currentChunk);
                                $currentChunk = $p;
                            } else {
                                $currentChunk = empty($currentChunk) ? $p : $currentChunk . "\n\n" . $p;
                            }
                        }
                        if (!empty($currentChunk)) {
                            $chunks[] = trim($currentChunk);
                        }

                        foreach ($chunks as $idx => $chunk) {
                            if ($idx > 0 && config('app.env') !== 'testing') {
                                $this->whatsappService->sendPresence($conversation->contact->phone, 'composing');
                                sleep(rand(2, 4));
                            }
                            $this->whatsappService->sendTextMessage($conversation->contact->phone, $chunk);
                        }
                    } else {
                        $this->whatsappService->sendTextMessage($conversation->contact->phone, $content);
                    }

                    // Finaliza status de presença
                    $this->whatsappService->sendPresence($conversation->contact->phone, 'paused');
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
