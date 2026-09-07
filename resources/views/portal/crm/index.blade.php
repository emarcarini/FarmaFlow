@extends('layouts.app', ['title' => 'CRM & Carteira de Clientes'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header & Search Filter -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Carteira de Clientes & CRM</h2>
            <p class="text-sm text-slate-400 mt-1">Gestão de empresas, múltiplos contatos, histórico comercial e score RFM.</p>
        </div>
    </div>

    <!-- Search and Filters Bar -->
    <div class="p-4 rounded-2xl bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-lg">
        <form method="GET" action="{{ route('portal.crm') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="relative md:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por razão social, nome fantasia, CNPJ ou contato..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <select name="segment" class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos os Segmentos</option>
                    @foreach($segments as $s)
                        <option value="{{ $s }}" {{ $segment === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filtrar Carteira</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Companies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($companies as $comp)
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                <div>
                    <!-- Top Badge & Classification -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $comp->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($comp->status === 'at_risk' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') }}">
                            {{ strtoupper($comp->status) }}
                        </span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">
                            Classe {{ $comp->classification }}
                        </span>
                    </div>

                    <!-- Company Name -->
                    <h3 class="font-display font-bold text-lg text-white leading-snug">{{ $comp->trade_name ?? $comp->name }}</h3>
                    <p class="text-xs text-slate-400 mt-1">{{ $comp->name }} • CNPJ: {{ $comp->document ?? 'Não inf.' }}</p>

                    <!-- Tags -->
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach($comp->tags as $tag)
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md" style="background: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40">
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>

                    <!-- Primary Contact -->
                    <div class="mt-4 pt-4 border-t border-slate-800/80 text-xs text-slate-400 space-y-1">
                        <p><span class="font-semibold text-slate-300">Contato:</span> {{ $comp->primaryContact?->name ?? 'Sem contato cadastrado' }}</p>
                        <p><span class="font-semibold text-slate-300">WhatsApp:</span> {{ $comp->primaryContact?->phone ?? $comp->phone ?? 'Não inf.' }}</p>
                        <p><span class="font-semibold text-slate-300">Cidade:</span> {{ $comp->city ?? 'N/A' }} - {{ $comp->state ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Footer with Score & CTA -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Score RFM</span>
                        <span class="font-display font-bold text-base text-brand-400">{{ $comp->score?->overall_score ?? 75 }}/100</span>
                    </div>
                    <a href="{{ route('portal.crm.show', $comp) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all flex items-center gap-1.5">
                        <span>Ver Perfil & Timeline</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-sm text-slate-500">
                Nenhum cliente encontrado com os filtros selecionados.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $companies->links() }}
    </div>
</div>
@endsection
