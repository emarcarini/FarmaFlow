@extends('layouts.app', ['title' => 'Catálogo & Preços'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                <span>Tabela Determinística</span>
            </div>
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Catálogo de Produtos & Políticas de Preço</h2>
            <p class="text-sm text-slate-400 mt-1">Preços base, descontos escalonados por volume e campanhas sazonais automáticas.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center gap-3 shadow-lg">
                <i data-lucide="package-check" class="w-5 h-5 text-indigo-400"></i>
                <div class="text-xs">
                    <span class="text-slate-400 block">Total de Medicamentos</span>
                    <span class="text-white font-bold font-display text-sm">{{ $products->total() }} Itens</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Campaigns Banner Grid -->
    @if($campaigns->isNotEmpty())
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-4 h-4 text-emerald-400"></i>
                <span>Campanhas Comerciais Vigentes</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($campaigns as $camp)
                    <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-950/40 via-slate-900/90 to-teal-950/30 border border-emerald-500/20 shadow-xl relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-xl text-xs font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono tracking-wider">
                                {{ $camp->code }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-500"></i>
                                Até {{ $camp->ends_at->format('d/m/Y') }}
                            </span>
                        </div>
                        <h4 class="font-display font-bold text-lg text-white mt-3 group-hover:text-emerald-300 transition-colors">{{ $camp->name }}</h4>
                        <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">{{ $camp->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Meraki UI Search & Filter Bar -->
    <div class="p-5 rounded-3xl bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-xl">
        <form method="GET" action="{{ route('portal.catalog') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="relative md:col-span-6">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por medicamento, princípio ativo, código SKU..."
                    class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all">
            </div>

            <div class="md:col-span-3">
                <div class="relative">
                    <i data-lucide="folder" class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <select name="category" class="w-full pl-11 pr-8 py-3 bg-slate-950/70 border border-slate-800 rounded-2xl text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                        <option value="">Todas as Categorias</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
                @if($search || $category)
                    <a href="{{ route('portal.catalog') }}" class="py-3 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-2xl font-semibold text-sm transition-all flex items-center justify-center" title="Limpar Filtros">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Meraki UI Products Table -->
    <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/70 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="p-5 pl-6">Medicamento / SKU</th>
                        <th class="p-5">Categoria</th>
                        <th class="p-5">Preço Base</th>
                        <th class="p-5">Tabela Escalonada (Volume)</th>
                        <th class="p-5">Estoque</th>
                        <th class="p-5 pr-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($products as $prod)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-5 pl-6">
                                <div class="font-bold text-white text-base">{{ $prod->name }}</div>
                                <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                                    <span>{{ $prod->presentation ?? 'Sem apresentação' }}</span>
                                    <span class="text-slate-600">•</span>
                                    <span class="font-mono text-slate-500 font-medium">SKU: {{ $prod->code }}</span>
                                </div>
                            </td>
                            <td class="p-5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $prod->category ?? 'Geral' }}
                                </span>
                            </td>
                            <td class="p-5">
                                <span class="font-mono font-bold text-white text-base">
                                    R$ {{ number_format($prod->base_price, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($prod->prices as $price)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-mono bg-slate-950/80 border border-slate-800 text-indigo-300">
                                            <span class="text-slate-400 font-sans font-medium">{{ $price->min_quantity }}{{ $price->max_quantity ? '-'.$price->max_quantity : '+' }} un:</span>
                                            <span class="font-bold text-white">R$ {{ number_format($price->unit_price, 2, ',', '.') }}</span>
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500">Sem faixas adicionais</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="p-5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $prod->stock_quantity > 20 ? 'bg-emerald-400' : ($prod->stock_quantity > 0 ? 'bg-amber-400' : 'bg-rose-400') }}"></span>
                                    <span class="font-mono text-xs font-bold {{ $prod->stock_quantity > 0 ? 'text-white' : 'text-rose-400' }}">
                                        {{ $prod->stock_quantity }} {{ $prod->unit }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-5 pr-6 text-right">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $prod->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $prod->is_active ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                    {{ $prod->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-sm text-slate-500">
                                Nenhum medicamento encontrado com os critérios de busca.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
