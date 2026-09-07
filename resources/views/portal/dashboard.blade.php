@extends('layouts.app', ['title' => 'Dashboard Comercial'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- HeroUI Header Banner -->
    <div class="relative overflow-hidden rounded-3xl heroui-card p-8 shadow-sm dark:shadow-xl">
        <div class="absolute -right-10 -top-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 dark:border-indigo-500/30 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Painel em Tempo Real
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">• Meta Mensal FarmaFlow</span>
                </div>
                <h2 class="font-display text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Olá, {{ auth()->user()->name }} 👋
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                    Seu assistente comercial de IA com Gemini está ativo no WhatsApp atendendo seus clientes e gerando cotações determinísticas.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.inbox') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white text-sm font-bold transition-all shadow-lg shadow-indigo-600/25 flex items-center gap-2.5">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Abrir WhatsApp Live</span>
                </a>
            </div>
        </div>
    </div>

    <!-- HeroUI KPI Metric Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Revenue Card -->
        <div class="heroui-card rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 shadow-sm dark:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Vendas do Mês</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-all">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white">R$ {{ number_format($monthlyRevenue, 2, ',', '.') }}</h3>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                        <span>Meta: R$ {{ number_format($monthlyGoal, 0, ',', '.') }}</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $goalProgress }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-200 dark:border-slate-800">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2.5 rounded-full transition-all duration-500" style="width: {{ min($goalProgress, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Orders Count -->
        <div class="heroui-card rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 shadow-sm dark:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pedidos Faturados</span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-all">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white">{{ $ordersCount }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center">
                        <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        100%
                    </span>
                    com trava de preço validada
                </p>
            </div>
        </div>

        <!-- 3. Open Quotes -->
        <div class="heroui-card rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 shadow-sm dark:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Cotações Abertas</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-all">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white">{{ $activeQuotesCount }}</h3>
                <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mt-2">
                    R$ {{ number_format($activeQuotesAmount, 2, ',', '.') }} em negociação
                </p>
            </div>
        </div>

        <!-- 4. Tasks & Handover -->
        <div class="heroui-card rounded-3xl p-6 relative overflow-hidden group hover:border-indigo-500/40 shadow-sm dark:shadow-xl transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tarefas & Handover</span>
                <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-all">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex items-center gap-2.5">
                    <h3 class="font-display text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white">{{ $pendingTasksCount }}</h3>
                    @if($handoverConversationsCount > 0)
                        <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 dark:border-rose-500/30 animate-pulse">
                            {{ $handoverConversationsCount }} Humano
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">{{ $overdueTasksCount }} tarefas pendentes na fila</p>
            </div>
        </div>
    </div>

    <!-- HeroUI Two Columns Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Recent Orders Table (8 cols) -->
        <div class="lg:col-span-8 heroui-card rounded-3xl p-6 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Últimos Pedidos Confirmados</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Histórico de conversões fechadas pelo portal e WhatsApp</p>
                    </div>
                </div>
                <a href="{{ route('portal.sales', ['tab' => 'orders']) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 transition-colors flex items-center gap-1">
                    <span>Ver todos</span>
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>

            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800/60">
                @forelse($recentOrders as $order)
                    <div class="py-4 flex items-center justify-between gap-4 hover:bg-slate-50/80 dark:hover:bg-slate-900/40 px-3 rounded-2xl transition-all">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-xs font-bold text-slate-700 dark:text-slate-300 shadow-sm">
                                {{ strtoupper(substr($order->company?->trade_name ?? 'CL', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ $order->company?->trade_name ?? 'Cliente Avulso' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $order->order_number }} • {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-extrabold text-slate-900 dark:text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</p>
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider {{ $order->status === 'confirmed' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30' }}">
                                {{ strtoupper($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <i data-lucide="inbox" class="w-8 h-8 text-slate-400 dark:text-slate-600 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Nenhum pedido recente registrado.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Active Campaigns & Docs Cards (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Campaigns Card -->
            <div class="heroui-card rounded-3xl p-6 shadow-sm dark:shadow-xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-slate-800/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                        </div>
                        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white">Campanhas Ativas</h3>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ count($activeCampaigns) }} ativas</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($activeCampaigns as $camp)
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $camp->name }}</h4>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-500/30">Vigente</span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1.5 leading-relaxed">{{ $camp->description }}</p>
                            <div class="mt-3 pt-2.5 border-t border-slate-200 dark:border-slate-800/60 flex items-center justify-between text-[11px] text-slate-500">
                                <span>Expira em:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $camp->ends_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400 text-center py-6">Nenhuma campanha vigente no momento.</p>
                    @endforelse
                </div>
            </div>

            <!-- HeroUI Docs Quick Card -->
            <div class="rounded-3xl p-6 relative overflow-hidden bg-gradient-to-br from-indigo-500/10 via-slate-100 to-white dark:from-indigo-950/60 dark:via-slate-900 dark:to-slate-900 border border-indigo-500/20 shadow-sm dark:shadow-xl">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-2xl bg-indigo-600/10 dark:bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white">Manual do FarmaFlow</h4>
                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">Central de Ajuda /docs</span>
                    </div>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Aprenda a cadastrar faixas de preço por volume, aplicar campanhas e usar o assistente IA.
                </p>
                <a href="{{ route('portal.docs') }}" class="mt-4 w-full py-2.5 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20">
                    <span>Acessar Documentação Completa</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

