@extends('layouts.app', ['title' => $company->trade_name ?? $company->name])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Breadcrumb and Quick Nav -->
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.crm') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 text-xs font-semibold text-slate-400 hover:text-white transition-all">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Voltar para Carteira de Clientes</span>
        </a>
    </div>

    <!-- Company Header Hero Card -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900/90 via-slate-900/95 to-indigo-950/40 backdrop-blur-2xl border border-slate-800/80 rounded-3xl p-8 shadow-xl">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-indigo-900 border border-indigo-400/30 flex items-center justify-center font-display font-extrabold text-2xl text-white shadow-lg flex-shrink-0">
                    {{ strtoupper(substr($company->trade_name ?? $company->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-1.5">
                        <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                            {{ $company->trade_name ?? $company->name }}
                        </h2>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $company->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($company->status === 'at_risk' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $company->status === 'active' ? 'bg-emerald-400' : ($company->status === 'at_risk' ? 'bg-rose-400' : 'bg-amber-400') }}"></span>
                            {{ $company->status === 'active' ? 'Ativo' : ($company->status === 'at_risk' ? 'Em Risco' : 'Inativo') }}
                        </span>
                        <span class="text-xs font-bold px-3 py-1 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 font-mono">
                            Classe {{ $company->classification }}
                        </span>
                    </div>
                    <p class="text-xs md:text-sm text-slate-400">
                        <span class="text-slate-300 font-medium">Razão Social:</span> {{ $company->name }} • 
                        <span class="text-slate-300 font-medium">CNPJ:</span> <span class="font-mono">{{ $company->document ?? 'Não inf.' }}</span> • 
                        <span class="text-slate-300 font-medium">Segmento:</span> {{ $company->segment ?? 'Farmácia Geral' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('portal.inbox') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-semibold transition-all shadow-lg shadow-emerald-600/25 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                    <span>Conversar no WhatsApp</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2 Columns: Score & Contacts vs Unified Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: RFM Score & Contacts Info (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Score RFM Card -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="font-display font-bold text-base text-white flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-400"></i>
                        <span>Score Comercial RFM</span>
                    </h3>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        IA Preditiva
                    </span>
                </div>

                <div class="mt-6 text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-950/60 to-slate-900 border border-indigo-500/30 text-indigo-300 font-display font-extrabold text-4xl shadow-xl shadow-indigo-950/40">
                        {{ $company->score?->overall_score ?? 70 }}
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-slate-400">Tendência de Compra:</span>
                        <span class="text-xs font-bold uppercase ml-1 {{ ($company->score?->trend ?? 'Estável') === 'Crescimento' ? 'text-emerald-400' : 'text-slate-200' }}">
                            {{ $company->score?->trend ?? 'Estável' }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-4 text-xs">
                    <div>
                        <div class="flex justify-between text-slate-300 font-medium mb-1.5">
                            <span>Recência (Última compra)</span>
                            <span class="font-bold text-indigo-400 font-mono">{{ $company->score?->recency_score ?? 60 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 p-0.5 border border-slate-800 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-400 h-full rounded-full transition-all" style="width: {{ $company->score?->recency_score ?? 60 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-slate-300 font-medium mb-1.5">
                            <span>Frequência de Pedidos</span>
                            <span class="font-bold text-indigo-400 font-mono">{{ $company->score?->frequency_score ?? 80 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 p-0.5 border border-slate-800 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-400 h-full rounded-full transition-all" style="width: {{ $company->score?->frequency_score ?? 80 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-slate-300 font-medium mb-1.5">
                            <span>Volume Financeiro</span>
                            <span class="font-bold text-indigo-400 font-mono">{{ $company->score?->monetary_score ?? 75 }}/100</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 p-0.5 border border-slate-800 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-cyan-400 h-full rounded-full transition-all" style="width: {{ $company->score?->monetary_score ?? 75 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs text-slate-400 space-y-1">
                    <p class="font-bold text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Diagnóstico do Assistente:</span>
                    </p>
                    <p class="leading-relaxed">{{ $company->score?->explanation['summary'] ?? 'Cliente com bom padrão de recompra. Recomenda-se acompanhamento quinzenal para novas cotações.' }}</p>
                </div>
            </div>

            <!-- Contacts Card -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <h3 class="font-display font-bold text-base text-white flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>Contatos Cadastrados</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">{{ $company->contacts->count() }} contatos</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($company->contacts as $ct)
                        <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-white text-sm">{{ $ct->name }}</h4>
                                @if($ct->is_primary)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Principal</span>
                                @endif
                            </div>
                            <p class="text-slate-400">{{ $ct->role_position ?? 'Comprador' }}</p>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                                <span class="text-slate-300 font-mono font-medium">{{ $ct->phone }}</span>
                                <span class="text-[10px] font-bold {{ $ct->isOptedOut('whatsapp') ? 'text-rose-400' : 'text-emerald-400' }}">
                                    {{ $ct->isOptedOut('whatsapp') ? 'Bloqueado (Opt-Out)' : 'Opt-In Ativo' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-6">Nenhum contato cadastrado para este cliente.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Unified Chronological Timeline (8 cols) -->
        <div class="lg:col-span-8 bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center justify-between pb-6 border-b border-slate-800">
                <div>
                    <h3 class="font-display font-bold text-lg text-white flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-indigo-400"></i>
                        <span>Timeline Unificada de Atendimento & Vendas</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Interações automáticas pelo robô e ações registradas pelo representante.</p>
                </div>
                <span class="text-xs font-mono text-slate-400 bg-slate-800 px-3 py-1 rounded-xl border border-slate-700">
                    {{ $timeline->count() }} eventos
                </span>
            </div>

            <!-- Timeline Stream -->
            <div class="mt-8 space-y-6 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-slate-800">
                @forelse($timeline as $event)
                    <div class="relative flex items-start gap-4">
                        <!-- Icon Circle -->
                        <div class="w-7 h-7 rounded-full bg-slate-900 border border-slate-700 flex items-center justify-center text-indigo-400 relative z-10 flex-shrink-0 shadow-md">
                            <i data-lucide="{{ $event['icon'] ?? 'circle' }}" class="w-3.5 h-3.5"></i>
                        </div>

                        <!-- Event Content Card -->
                        <div class="flex-1 bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition-colors">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="text-xs font-bold text-white">{{ $event['title'] }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $event['timestamp']?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">{{ $event['description'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center text-sm text-slate-500">
                        Nenhum evento registrado nesta timeline até o momento.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
