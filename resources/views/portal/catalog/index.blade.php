@extends('layouts.app', ['title' => 'Catálogo & Preços'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Catálogo de Produtos & Preços Determinísticos</h2>
            <p class="text-sm text-slate-400 mt-1">Preços base, tabelas escalonadas por volume e campanhas sazonais ativas.</p>
        </div>
    </div>

    <!-- Active Campaigns Banner Grid -->
    @if($campaigns->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($campaigns as $camp)
                <div class="p-5 rounded-3xl bg-gradient-to-r from-emerald-950/40 via-slate-900/90 to-teal-950/30 border border-emerald-500/20 shadow-xl">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            {{ $camp->code }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">Válida até {{ $camp->ends_at->format('d/m/Y') }}</span>
                    </div>
                    <h3 class="font-display font-bold text-lg text-white mt-2">{{ $camp->name }}</h3>
                    <p class="text-xs text-slate-300 mt-1">{{ $camp->description }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <div class="p-4 rounded-2xl bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 shadow-lg">
        <form method="GET" action="{{ route('portal.catalog') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="relative md:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por medicamento, princípio ativo, código..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <select name="category" class="w-full py-2.5 px-3 bg-slate-950/70 border border-slate-800 rounded-xl text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todas as Categorias</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-semibold text-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Filtrar Catálogo</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="p-4 pl-6">Produto / Apresentação</th>
                    <th class="p-4">Categoria</th>
                    <th class="p-4">Preço Base</th>
                    <th class="p-4">Faixas de Preço por Quantidade</th>
                    <th class="p-4">Estoque</th>
                    <th class="p-4 pr-6 text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($products as $prod)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="p-4 pl-6">
                            <div class="font-bold text-white">{{ $prod->name }}</div>
                            <div class="text-xs text-slate-500">{{ $prod->presentation ?? 'Sem apresentação' }} • Código: {{ $prod->code }}</div>
                        </td>
                        <td class="p-4 text-xs font-semibold text-slate-400">
                            {{ $prod->category ?? 'Geral' }}
                        </td>
                        <td class="p-4 font-mono font-bold text-white">
                            R$ {{ number_format($prod->base_price, 2, ',', '.') }}
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($prod->prices as $price)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono bg-slate-950/80 border border-slate-800 text-brand-300">
                                        <span class="text-slate-400 font-sans">{{ $price->min_quantity }}{{ $price->max_quantity ? '-'.$price->max_quantity : '+' }} un:</span>
                                        <span class="font-bold text-white">R$ {{ number_format($price->unit_price, 2, ',', '.') }}</span>
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-500">Sem faixas extras</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="p-4 font-mono text-xs">
                            <span class="{{ $prod->stock_quantity > 0 ? 'text-emerald-400' : 'text-red-400' }} font-bold">
                                {{ $prod->stock_quantity }} {{ $prod->unit }}
                            </span>
                        </td>
                        <td class="p-4 pr-6 text-right">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $prod->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                {{ $prod->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-sm text-slate-500">
                            Nenhum produto cadastrado com os filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
