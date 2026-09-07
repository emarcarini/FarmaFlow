@extends('layouts.app', ['title' => 'Dashboard Comercial'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header with Welcome & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Painel de Vendas & Performance</h2>
            <p class="text-sm text-slate-400 mt-1">Acompanhamento em tempo real das metas, conversões do WhatsApp e oportunidades da carteira.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('portal.inbox') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>Ver WhatsApp Live</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Revenue Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Vendas do Mês</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl font-bold text-white">R$ {{ number_format($monthlyRevenue, 2, ',', '.') }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                        <span>Meta: R$ {{ number_format($monthlyGoal, 0, ',', '.') }}</span>
                        <span class="font-semibold text-emerald-400">{{ $goalProgress }}%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full" style="width: {{ $goalProgress }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Count -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pedidos Fechados</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl font-bold text-white">{{ $ordersCount }}</h3>
                <p class="text-xs text-slate-400 mt-1">Total de pedidos faturados neste mês</p>
            </div>
        </div>

        <!-- Open Quotes -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cotações Abertas</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl font-bold text-white">{{ $activeQuotesCount }}</h3>
                <p class="text-xs text-amber-400/90 font-medium mt-1">R$ {{ number_format($activeQuotesAmount, 2, ',', '.') }} em negociação</p>
            </div>
        </div>

        <!-- Pending Tasks & Handover -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tarefas & Handover</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center gap-3">
                    <h3 class="font-display text-2xl font-bold text-white">{{ $pendingTasksCount }}</h3>
                    @if($handoverConversationsCount > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                            {{ $handoverConversationsCount }} Humano
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">{{ $overdueTasksCount }} tarefas atrasadas</p>
            </div>
        </div>
    </div>

    <!-- Two Columns: Recent Orders & Active Campaigns -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Recent Orders (8 cols) -->
        <div class="lg:col-span-8 bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="font-display font-semibold text-base text-white flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-indigo-400"></i>
                    <span>Últimos Pedidos Confirmados</span>
                </h3>
                <a href="{{ route('portal.sales', ['tab' => 'orders']) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">Ver Todos →</a>
            </div>

            <div class="mt-4 divide-y divide-slate-800/60">
                @forelse($recentOrders as $order)
                    <div class="py-3.5 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xs font-bold text-slate-300">
                                {{ substr($order->company?->trade_name ?? 'CL', 0, 2) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white leading-snug">{{ $order->company?->trade_name ?? 'Cliente Avulso' }}</p>
                                <p class="text-xs text-slate-500">{{ $order->order_number }} • {{ $order->created_at->format('d/m H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</p>
                            <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $order->status === 'confirmed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                {{ strtoupper($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">Nenhum pedido recente registrado.</p>
                @endforelse
            </div>
        </div>

        <!-- Active Campaigns & Fast Info (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Campaigns Card -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
                <h3 class="font-display font-semibold text-base text-white flex items-center gap-2 pb-4 border-b border-slate-800">
                    <i data-lucide="tag" class="w-4 h-4 text-emerald-400"></i>
                    <span>Campanhas Ativas</span>
                </h3>

                <div class="mt-4 space-y-3">
                    @forelse($activeCampaigns as $camp)
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-white">{{ $camp->name }}</h4>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Vigente</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ $camp->description }}</p>
                            <p class="text-[11px] text-slate-500 mt-2">Válida até {{ $camp->ends_at->format('d/m/Y') }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">Nenhuma campanha vigente no momento.</p>
                    @endforelse
                </div>
            </div>

            <!-- Docs Shortcut Card -->
            <div class="bg-gradient-to-br from-indigo-900/40 to-slate-900 border border-indigo-500/20 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="book-open" class="w-5 h-5 text-indigo-400"></i>
                    <h4 class="font-display font-semibold text-sm text-white">Manual do Representante</h4>
                </div>
                <p class="text-xs text-slate-400">Precisa de ajuda com regras de preço, comandos da IA ou transferências humanas?</p>
                <a href="{{ route('portal.docs') }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-indigo-300 hover:text-indigo-200">
                    <span>Acessar rota /docs</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
