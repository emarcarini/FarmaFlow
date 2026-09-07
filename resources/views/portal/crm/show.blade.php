@extends('layouts.app', ['title' => $company->trade_name ?? $company->name])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Breadcrumb and Top Nav -->
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.crm') }}" class="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Voltar para Carteira de Clientes</span>
        </a>
    </div>

    <!-- Company Header Card -->
    <div class="bg-slate-900/80 backdrop-blur-2xl border border-slate-800/80 rounded-3xl p-8 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="font-display text-2xl font-bold text-white">{{ $company->trade_name ?? $company->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $company->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                        {{ strtoupper($company->status) }}
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">
                        Classe {{ $company->classification }}
                    </span>
                </div>
                <p class="text-sm text-slate-400">
                    Razão Social: {{ $company->name }} • CNPJ: {{ $company->document ?? 'Não inf.' }} • Segmento: {{ $company->segment ?? 'Geral' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('portal.inbox') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                    <span>Conversar no WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2 Columns: Score & Contacts (4 cols) vs Unified Timeline (8 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: RFM Score & Contacts Info (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Score RFM Card -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
                <h3 class="font-display font-semibold text-base text-white flex items-center gap-2 pb-4 border-b border-slate-800">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-brand-400"></i>
                    <span>Score Comercial RFM</span>
                </h3>

                <div class="mt-4 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-brand-600/15 border border-brand-500/30 text-brand-400 font-display font-bold text-3xl shadow-lg">
                        {{ $company->score?->overall_score ?? 70 }}
                    </div>
                    <p class="text-xs font-semibold text-slate-300 mt-2">Tendência: <span class="uppercase text-emerald-400 font-bold">{{ $company->score?->trend ?? 'Estável' }}</span></p>
                </div>

                <div class="mt-6 space-y-3 text-xs">
                    <div>
                        <div class="flex justify-between text-slate-400 mb-1">
                            <span>Recência (Última compra)</span>
                            <span class="font-bold text-white">{{ $company->score?->recency_score ?? 60 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $company->score?->recency_score ?? 60 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-slate-400 mb-1">
                            <span>Frequência de Pedidos</span>
                            <span class="font-bold text-white">{{ $company->score?->frequency_score ?? 80 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $company->score?->frequency_score ?? 80 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-slate-400 mb-1">
                            <span>Volume Financeiro (Monetário)</span>
                            <span class="font-bold text-white">{{ $company->score?->monetary_score ?? 75 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $company->score?->monetary_score ?? 75 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400">
                    <p class="font-semibold text-slate-300 mb-1">Diagnóstico:</p>
                    <p>{{ $company->score?->explanation['summary'] ?? 'Score calculado com base no histórico de transações.' }}</p>
                </div>
            </div>

            <!-- Contacts Card -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
                <h3 class="font-display font-semibold text-base text-white flex items-center gap-2 pb-4 border-b border-slate-800">
                    <i data-lucide="user" class="w-4 h-4 text-emerald-400"></i>
                    <span>Contatos Cadastrados</span>
                </h3>

                <div class="mt-4 space-y-3">
                    @forelse($company->contacts as $ct)
                        <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-white">{{ $ct->name }}</h4>
                                @if($ct->is_primary)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Principal</span>
                                @endif
                            </div>
                            <p class="text-slate-400 mt-0.5">{{ $ct->role_position ?? 'Comprador' }}</p>
                            <p class="text-slate-300 font-mono mt-2">WhatsApp: {{ $ct->phone }}</p>
                            <div class="mt-2 pt-2 border-t border-slate-800 flex items-center justify-between">
                                <span class="text-[10px] text-slate-500">LGPD:</span>
                                <span class="text-[10px] font-bold {{ $ct->isOptedOut('whatsapp') ? 'text-red-400' : 'text-emerald-400' }}">
                                    {{ $ct->isOptedOut('whatsapp') ? 'Opt-Out (Bloqueado)' : 'Opt-In Ativo' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">Nenhum contato cadastrado.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Unified Chronological Timeline (8 cols) -->
        <div class="lg:col-span-8 bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center justify-between pb-6 border-b border-slate-800">
                <h3 class="font-display font-semibold text-lg text-white flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-indigo-400"></i>
                    <span>Timeline Unificada de Atendimento & Vendas</span>
                </h3>
                <span class="text-xs text-slate-500">{{ $timeline->count() }} eventos</span>
            </div>

            <!-- Timeline Stream -->
            <div class="mt-8 space-y-6 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-slate-800">
                @forelse($timeline as $event)
                    <div class="relative flex items-start gap-4">
                        <!-- Icon Circle -->
                        <div class="w-7 h-7 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center text-indigo-400 relative z-10 flex-shrink-0">
                            <i data-lucide="{{ $event['icon'] ?? 'circle' }}" class="w-3.5 h-3.5"></i>
                        </div>

                        <!-- Event Content -->
                        <div class="flex-1 bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-xs font-bold text-white">{{ $event['title'] }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ $event['timestamp']?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed mt-1">{{ $event['description'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-sm text-slate-500">
                        Nenhum evento registrado nesta timeline até o momento.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
