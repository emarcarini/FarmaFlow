@extends('layouts.app', ['title' => 'Cotações & Pedidos'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                <span>Gestão de Fechamento</span>
            </div>
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-white tracking-tight">Cotações Comerciais & Pedidos de Venda</h2>
            <p class="text-sm text-slate-400 mt-1">Acompanhe orçamentos abertos pelo WhatsApp, fechamento com trava de preço e alçadas de desconto.</p>
        </div>
    </div>

    <!-- Meraki UI Navigation Tabs -->
    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800/80 w-fit backdrop-blur-xl">
        <a href="{{ route('portal.sales', ['tab' => 'quotes']) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $tab === 'quotes' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            <i data-lucide="file-text" class="w-4 h-4"></i>
            <span>Cotações em Aberto</span>
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'quotes' ? 'bg-indigo-700/80 text-white' : 'bg-slate-800 text-slate-400' }}">
                {{ $quotes->total() }}
            </span>
        </a>
        <a href="{{ route('portal.sales', ['tab' => 'orders']) }}"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $tab === 'orders' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            <span>Pedidos Faturados</span>
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $tab === 'orders' ? 'bg-indigo-700/80 text-white' : 'bg-slate-800 text-slate-400' }}">
                {{ $orders->total() }}
            </span>
        </a>
    </div>

    @if($tab === 'quotes')
        <!-- Quotes Table -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/70 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-5 pl-6">Cotação / Data</th>
                            <th class="p-5">Cliente / Contato</th>
                            <th class="p-5">Itens</th>
                            <th class="p-5">Total Orçado</th>
                            <th class="p-5">Validade</th>
                            <th class="p-5">Status</th>
                            <th class="p-5 pr-6 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($quotes as $quote)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-5 pl-6">
                                    <div class="font-mono font-bold text-white text-base">{{ $quote->quote_number }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-white">{{ $quote->company?->trade_name ?? 'Cliente Avulso' }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $quote->contact?->name ?? 'Sem contato' }}</div>
                                </td>
                                <td class="p-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-medium bg-slate-950/80 border border-slate-800 text-slate-300">
                                        <i data-lucide="package" class="w-3.5 h-3.5 text-indigo-400"></i>
                                        <span>{{ $quote->items->count() }} itens</span>
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="font-mono font-bold text-emerald-400 text-base">
                                        R$ {{ number_format($quote->total_amount, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="font-mono text-xs font-medium {{ $quote->isExpired() ? 'text-rose-400 font-bold' : 'text-slate-400' }}">
                                        {{ $quote->expires_at?->format('d/m/Y') ?? 'N/A' }}
                                        @if($quote->isExpired())
                                            <span class="block text-[10px] text-rose-500 font-sans">Expirada</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $quote->status === 'converted_to_order' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $quote->status === 'converted_to_order' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $quote->status === 'converted_to_order' ? 'Convertida' : 'Em Aberto' }}
                                    </span>
                                </td>
                                <td class="p-5 pr-6 text-right">
                                    @if($quote->status !== 'converted_to_order')
                                        <form method="POST" action="{{ route('portal.sales.convert', $quote) }}">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-semibold transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5 ml-auto">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Fechar Pedido</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1 justify-end">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                            <span>Pedido Fechado</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-sm text-slate-500">
                                    Nenhuma cotação registrada no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pt-4">{{ $quotes->links() }}</div>
    @else
        <!-- Orders Table -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/70 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="p-5 pl-6">Pedido / Data</th>
                            <th class="p-5">Cliente / Comprador</th>
                            <th class="p-5">Condição de Pagamento</th>
                            <th class="p-5">Valor Total</th>
                            <th class="p-5">Status</th>
                            <th class="p-5 pr-6 text-right">Alçada & Liberação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-5 pl-6">
                                    <div class="font-mono font-bold text-white text-base">{{ $order->order_number }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="p-5">
                                    <div class="font-bold text-white">{{ $order->company?->trade_name ?? 'Cliente Avulso' }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $order->contact?->name ?? 'Sem contato' }}</div>
                                </td>
                                <td class="p-5">
                                    <span class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-950/80 border border-slate-800 text-slate-300">
                                        {{ $order->payment_terms ?? '28/35/42 ddl' }}
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="font-mono font-bold text-white text-base">
                                        R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold {{ $order->status === 'confirmed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $order->status === 'confirmed' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $order->status === 'confirmed' ? 'Confirmado' : 'Aguardando Aprovação' }}
                                    </span>
                                </td>
                                <td class="p-5 pr-6 text-right">
                                    @if($order->status === 'pending_approval')
                                        <form method="POST" action="{{ route('portal.sales.approve', $order) }}">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-all shadow-md shadow-amber-600/20 ml-auto">
                                                Aprovar Alçada
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1 justify-end">
                                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                                            <span>Aprovado</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-sm text-slate-500">
                                    Nenhum pedido faturado registrado no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="pt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
