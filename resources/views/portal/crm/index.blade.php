@extends('layouts.app', ['title' => 'CRM & Carteira de Clientes'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header & Quick Stats -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                <span>Gestão Comercial 360°</span>
            </div>
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Carteira de Clientes & CRM</h2>
            <p class="text-sm text-slate-400 mt-1">Visão completa de farmácias, distribuidoras, múltiplos contatos e score preditivo RFM.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center gap-3 shadow-lg">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                <div class="text-xs">
                    <span class="text-slate-400 block">Total na Carteira</span>
                    <span class="text-white font-bold font-display text-sm">{{ $companies->total() }} Empresas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Meraki UI Search and Filters Bar -->
    <div class="p-5 rounded-3xl bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-xl">
        <form method="GET" action="{{ route('portal.crm') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="relative md:col-span-6">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por razão social, nome fantasia, CNPJ ou cidade..."
                    class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all">
            </div>

            <div class="md:col-span-3">
                <div class="relative">
                    <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <select name="segment" class="w-full pl-11 pr-8 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">Todos os Segmentos</option>
                        @foreach($segments as $s)
                            <option value="{{ $s }}" {{ $segment === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="flex-1 py-3 px-5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-2xl font-semibold text-sm transition-all shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filtrar</span>
                </button>
                @if($search || $segment)
                    <a href="{{ route('portal.crm') }}" class="py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-2xl font-semibold text-sm transition-all flex items-center justify-center" title="Limpar Filtros">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Meraki UI Companies Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($companies as $comp)
            <div class="group bg-gradient-to-b from-slate-900/90 to-slate-950/90 backdrop-blur-xl border border-slate-800/80 hover:border-indigo-500/40 rounded-3xl p-6 shadow-xl hover:shadow-2xl hover:shadow-indigo-950/20 transition-all flex flex-col justify-between">
                <div>
                    <!-- Top Status & Class Badges -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $comp->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($comp->status === 'at_risk' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $comp->status === 'active' ? 'bg-emerald-400' : ($comp->status === 'at_risk' ? 'bg-rose-400' : 'bg-amber-400') }}"></span>
                                {{ $comp->status === 'active' ? 'Ativo' : ($comp->status === 'at_risk' ? 'Em Risco' : 'Inativo') }}
                            </span>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 font-mono">
                            Classe {{ $comp->classification }}
                        </span>
                    </div>

                    <!-- Company Title & Document -->
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-950 to-slate-800 border border-indigo-500/20 flex items-center justify-center font-bold text-sm text-indigo-400 flex-shrink-0 group-hover:scale-105 transition-transform">
                            {{ strtoupper(substr($comp->trade_name ?? $comp->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-display font-bold text-base text-white truncate group-hover:text-indigo-300 transition-colors">
                                {{ $comp->trade_name ?? $comp->name }}
                            </h3>
                            <p class="text-xs text-slate-400 truncate mt-0.5">{{ $comp->name }}</p>
                            <p class="text-[11px] text-slate-500 font-mono mt-0.5">CNPJ: {{ $comp->document ?? 'Não informado' }}</p>
                        </div>
                    </div>

                    <!-- Tags Chips -->
                    @if($comp->tags->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach($comp->tags as $tag)
                                <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-lg" style="background: {{ $tag->color }}15; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}30">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <!-- Primary Contact Section -->
                    <div class="mt-5 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-1.5 text-xs text-slate-400">
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="font-semibold flex items-center gap-1.5">
                                <i data-lucide="user" class="w-3.5 h-3.5 text-indigo-400"></i>
                                <span>{{ $comp->primaryContact?->name ?? 'Sem contato primário' }}</span>
                            </span>
                            @if($comp->primaryContact)
                                <span class="text-[10px] text-slate-500 font-medium">{{ $comp->primaryContact->role_position ?? 'Comprador' }}</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-800/60">
                            <span class="flex items-center gap-1 text-slate-400">
                                <i data-lucide="map-pin" class="w-3 h-3 text-slate-500"></i>
                                <span>{{ $comp->city ?? 'N/A' }} - {{ $comp->state ?? 'N/A' }}</span>
                            </span>
                            <span class="font-mono text-emerald-400 font-medium">
                                {{ $comp->primaryContact?->phone ?? $comp->phone ?? 'Sem tel' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer with RFM Score & CTA -->
                <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Score RFM</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="font-display font-extrabold text-lg {{ ($comp->score?->overall_score ?? 75) >= 70 ? 'text-emerald-400' : (($comp->score?->overall_score ?? 75) >= 40 ? 'text-amber-400' : 'text-rose-400') }}">
                                {{ $comp->score?->overall_score ?? 75 }}
                            </span>
                            <span class="text-[11px] text-slate-500 font-medium">/100</span>
                        </div>
                    </div>

                    <a href="{{ route('portal.crm.show', $comp) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white text-xs font-semibold transition-all flex items-center gap-2 shadow-sm group-hover:bg-indigo-600">
                        <span>Ver Perfil</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-14 h-14 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-500 mb-3">
                    <i data-lucide="users-2" class="w-6 h-6"></i>
                </div>
                <h3 class="font-display font-bold text-base text-white">Nenhum cliente encontrado</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Tente ajustar seus termos de busca ou filtros de segmento para localizar clientes.</p>
                <a href="{{ route('portal.crm') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all">
                    <span>Limpar Filtros</span>
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $companies->links() }}
    </div>
</div>
@endsection
