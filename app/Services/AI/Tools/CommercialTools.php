<?php

namespace App\Services\AI\Tools;

use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Task;
use App\Services\Audit\AuditLogService;
use App\Services\Pricing\PricingEngine;
use App\Services\Sales\OrderService;
use App\Services\Sales\QuoteService;
use App\Services\WhatsApp\HumanHandoverService;

class CommercialTools
{
    public function __construct(
        protected PricingEngine $pricingEngine,
        protected QuoteService $quoteService,
        protected OrderService $orderService,
        protected HumanHandoverService $handoverService
    ) {}

    /**
     * Retornar os esquemas JSON de todas as ferramentas para a OpenAI API (Function Calling).
     */
    public function getToolDefinitions(string $mode = 'customer'): array
    {
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_produto',
                    'description' => 'Busca produtos disponíveis no catálogo pelo nome, código ou categoria.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'termo' => [
                                'type' => 'string',
                                'description' => 'Nome do medicamento/produto ou princípio ativo (ex: Dipirona, Amoxicilina).',
                            ],
                            'categoria' => [
                                'type' => 'string',
                                'description' => 'Categoria opcional (ex: Analgésicos, Antibióticos).',
                            ],
                        ],
                        'required' => ['termo'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_preco',
                    'description' => 'Consulta determinísticamente o preço unitário e total de um produto conforme a quantidade e condições do cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'produto_id' => [
                                'type' => 'integer',
                                'description' => 'ID do produto no catálogo.',
                            ],
                            'quantidade' => [
                                'type' => 'integer',
                                'description' => 'Quantidade desejada de unidades ou caixas.',
                            ],
                        ],
                        'required' => ['produto_id', 'quantidade'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'consultar_campanhas_ativas',
                    'description' => 'Lista as campanhas comerciais e promoções vigentes.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'criar_cotacao',
                    'description' => 'Gera uma nova cotação comercial estruturada para o cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'itens' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'produto_id' => ['type' => 'integer'],
                                        'quantidade' => ['type' => 'integer'],
                                    ],
                                    'required' => ['produto_id', 'quantidade'],
                                ],
                                'description' => 'Lista de itens com produto_id e quantidade.',
                            ],
                            'condicoes_pagamento' => [
                                'type' => 'string',
                                'description' => 'Forma ou prazo de pagamento acordado.',
                            ],
                            'observacoes' => [
                                'type' => 'string',
                                'description' => 'Observações adicionais da negociação.',
                            ],
                        ],
                        'required' => ['itens'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'criar_pedido',
                    'description' => 'Fecha e converte uma cotação em pedido de venda, executando a revalidação comercial de segurança.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'cotacao_id' => [
                                'type' => 'integer',
                                'description' => 'ID da cotação a ser convertida em pedido.',
                            ],
                            'observacoes' => [
                                'type' => 'string',
                                'description' => 'Notas adicionais sobre a entrega ou faturamento.',
                            ],
                        ],
                        'required' => ['cotacao_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'transferir_para_humano',
                    'description' => 'Transfere o atendimento imediatamente para um representante humano e pausa o assistente automático.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'motivo' => [
                                'type' => 'string',
                                'description' => 'Motivo da transferência (ex: solicitou humano, dúvida clínica, negociação de grande porte).',
                            ],
                        ],
                        'required' => ['motivo'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'criar_followup',
                    'description' => 'Agenda uma tarefa de acompanhamento/follow-up para o cliente ou cotação.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'dias' => [
                                'type' => 'integer',
                                'description' => 'Em quantos dias deve ocorrer o contato (ex: 2 para depois de amanhã).',
                            ],
                            'motivo' => [
                                'type' => 'string',
                                'description' => 'Descrição do objetivo do follow-up.',
                            ],
                        ],
                        'required' => ['dias', 'motivo'],
                    ],
                ],
            ],
        ];

        // Ferramentas adicionais exclusivas para o Representante
        if ($mode === 'representative') {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'gerar_resumo_comercial',
                    'description' => 'Gera resumo consolidado das vendas, cotações abertas e tarefas do mês ou dia.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo' => [
                                'type' => 'string',
                                'enum' => ['hoje', 'semana', 'mes'],
                                'description' => 'Período desejado para análise.',
                            ],
                        ],
                        'required' => ['periodo'],
                    ],
                ],
            ];
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'listar_clientes_inativos',
                    'description' => 'Lista clientes da carteira que estão sem compras há mais de X dias.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'dias_sem_compra' => [
                                'type' => 'integer',
                                'description' => 'Mínimo de dias sem compra (padrão: 30).',
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $tools;
    }

    /**
     * Executar uma ferramenta chamada pela IA.
     */
    public function executeTool(
        string $toolName,
        array $arguments,
        ?Contact $contact = null,
        ?Company $company = null,
        ?Conversation $conversation = null
    ): array {
        AuditLogService::logAiAction("tool.executed.{$toolName}", null, $arguments);

        return match ($toolName) {
            'buscar_produto' => $this->executeSearchProduct($arguments),
            'consultar_preco' => $this->executeCheckPrice($arguments, $company, $contact),
            'consultar_campanhas_ativas' => $this->executeCheckCampaigns($company, $contact),
            'criar_cotacao' => $this->executeCreateQuote($arguments, $company, $contact),
            'criar_pedido' => $this->executeCreateOrder($arguments),
            'transferir_para_humano' => $this->executeHandover($arguments, $conversation),
            'criar_followup' => $this->executeCreateFollowup($arguments, $company, $contact),
            'gerar_resumo_comercial' => $this->executeCommercialSummary($arguments),
            'listar_clientes_inativos' => $this->executeInactiveCustomers($arguments),
            default => [
                'error' => "Ferramenta '{$toolName}' não reconhecida pelo sistema.",
            ],
        };
    }

    protected function executeSearchProduct(array $args): array
    {
        $term = $args['termo'] ?? '';
        $category = $args['categoria'] ?? null;

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->when($category, fn($q) => $q->where('category', $category))
            ->take(5)
            ->get();

        if ($products->isEmpty()) {
            return [
                'found' => false,
                'message' => "Nenhum produto encontrado com o termo '{$term}'.",
            ];
        }

        return [
            'found' => true,
            'products' => $products->map(fn(Product $p) => [
                'id' => $p->id,
                'codigo' => $p->code,
                'nome' => $p->name,
                'apresentacao' => $p->presentation,
                'preco_base' => (float) $p->base_price,
                'estoque_disponivel' => $p->stock_quantity,
                'categoria' => $p->category,
            ])->toArray(),
        ];
    }

    protected function executeCheckPrice(array $args, ?Company $company, ?Contact $contact): array
    {
        $productId = (int) $args['produto_id'];
        $quantity = (int) ($args['quantidade'] ?? 1);

        $product = Product::find($productId);
        if (!$product) {
            return ['error' => 'Produto não encontrado.'];
        }

        $result = $this->pricingEngine->resolvePrice(
            product: $product,
            quantity: $quantity,
            company: $company,
            contact: $contact,
            logAudit: true
        );

        return $result->toArray();
    }

    protected function executeCheckCampaigns(?Company $company, ?Contact $contact): array
    {
        $campaigns = CommercialCampaign::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('products.product')
            ->get();

        $eligible = $campaigns->filter(fn(CommercialCampaign $c) => $c->isEligibleForCustomer($company, $contact));

        return [
            'campanhas_ativas' => $eligible->map(fn(CommercialCampaign $c) => [
                'id' => $c->id,
                'nome' => $c->name,
                'codigo' => $c->code,
                'descricao' => $c->description,
                'vigencia_fim' => $c->ends_at->format('d/m/Y'),
                'produtos' => $c->products->map(fn($cp) => [
                    'produto' => $cp->product->name,
                    'preco_promocional' => (float) $cp->special_price,
                    'quantidade_minima' => $cp->min_quantity,
                ])->toArray(),
            ])->values()->toArray(),
        ];
    }

    protected function executeCreateQuote(array $args, ?Company $company, ?Contact $contact): array
    {
        $items = $args['itens'] ?? [];
        if (empty($items)) {
            return ['error' => 'Nenhum item informado para a cotação.'];
        }

        $quote = $this->quoteService->createQuote([
            'company_id' => $company?->id,
            'contact_id' => $contact?->id,
            'payment_terms' => $args['condicoes_pagamento'] ?? 'Padrão 30 dias',
            'notes' => $args['observacoes'] ?? 'Cotação gerada via WhatsApp pelo Agente IA',
            'created_source' => 'whatsapp_ai',
            'actor_type' => 'ai',
        ], $items);

        return [
            'sucesso' => true,
            'cotacao_id' => $quote->id,
            'numero_cotacao' => $quote->quote_number,
            'total' => (float) $quote->total_amount,
            'desconto_total' => (float) $quote->discount_total,
            'itens_count' => $quote->items->count(),
            'validade' => $quote->expires_at->format('d/m/Y'),
        ];
    }

    protected function executeCreateOrder(array $args): array
    {
        $quoteId = (int) $args['cotacao_id'];
        $quote = Quote::find($quoteId);

        if (!$quote) {
            return ['error' => 'Cotação não encontrada.'];
        }

        try {
            $order = $this->orderService->createFromQuote($quote, [
                'created_via' => 'whatsapp_ai',
                'actor_type' => 'ai',
                'notes' => $args['observacoes'] ?? 'Pedido fechado via WhatsApp pelo Agente IA',
            ]);

            return [
                'sucesso' => true,
                'pedido_id' => $order->id,
                'numero_pedido' => $order->order_number,
                'status' => $order->status,
                'requer_aprovacao' => $order->requires_approval,
                'total' => (float) $order->total_amount,
            ];
        } catch (\Throwable $e) {
            return [
                'sucesso' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function executeHandover(array $args, ?Conversation $conversation): array
    {
        $reason = $args['motivo'] ?? 'Solicitação de atendimento humano';

        if ($conversation) {
            $this->handoverService->triggerHandover($conversation, $reason);
        }

        return [
            'sucesso' => true,
            'status' => 'human_takeover',
            'mensagem' => 'Atendimento pausado para o robô e transferido para o representante humano.',
        ];
    }

    protected function executeCreateFollowup(array $args, ?Company $company, ?Contact $contact): array
    {
        $days = (int) ($args['dias'] ?? 2);
        $reason = $args['motivo'] ?? 'Follow-up de cotação';

        $scheduledDate = now()->addDays($days)->setHour(10)->setMinute(0);

        $task = Task::create([
            'company_id' => $company?->id,
            'contact_id' => $contact?->id,
            'representative_id' => $company?->representative_id ?? $contact?->representative_id,
            'title' => "Follow-up: {$contact?->name} ({$company?->trade_name})",
            'description' => $reason,
            'task_type' => 'followup',
            'priority' => 'medium',
            'due_date' => $scheduledDate,
            'status' => 'pending',
        ]);

        Followup::create([
            'task_id' => $task->id,
            'company_id' => $company?->id,
            'contact_id' => $contact?->id,
            'representative_id' => $task->representative_id,
            'scheduled_for' => $scheduledDate,
            'trigger_type' => 'manual',
            'status' => 'scheduled',
            'notes' => $reason,
        ]);

        return [
            'sucesso' => true,
            'tarefa_id' => $task->id,
            'agendado_para' => $scheduledDate->format('d/m/Y H:i'),
        ];
    }

    protected function executeCommercialSummary(array $args): array
    {
        $ordersCount = Order::whereMonth('created_at', now()->month)->count();
        $ordersTotal = (float) Order::whereMonth('created_at', now()->month)->sum('total_amount');
        $quotesOpen = Quote::where('status', 'draft')->orWhere('status', 'sent')->count();
        $tasksPending = Task::where('status', 'pending')->count();

        return [
            'pedidos_mes' => $ordersCount,
            'faturamento_mes' => $ordersTotal,
            'cotacoes_abertas' => $quotesOpen,
            'tarefas_pendentes' => $tasksPending,
        ];
    }

    protected function executeInactiveCustomers(array $args): array
    {
        $days = (int) ($args['dias_sem_compra'] ?? 30);
        $threshold = now()->subDays($days);

        $companies = Company::where('status', 'active')
            ->whereDoesntHave('orders', fn($q) => $q->where('created_at', '>=', $threshold))
            ->take(5)
            ->get();

        return [
            'clientes_inativos' => $companies->map(fn(Company $c) => [
                'id' => $c->id,
                'nome' => $c->trade_name ?? $c->name,
                'segmento' => $c->segment,
                'classificacao' => $c->classification,
            ])->toArray(),
        ];
    }
}
