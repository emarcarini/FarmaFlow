@extends('layouts.app', ['title' => 'Cotações & Pedidos'])

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">Cotações Comerciais & Pedidos de Venda</h2>
            <p class="text-sm text-slate-400 mt-1">Acompanhe orçamentos abertos, fechamento com trava determinística e alçadas de aprovação.</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 w-fit">
        <a href="{{ route('portal.sales', ['tab' => 'quotes']) }}"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $tab === 'quotes' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
            Cotações em Aberto ({{ $quotes->total() }})
        </a>
        <a href="{{ route('portal.sales', ['tab' => 'orders']) }}"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $tab === 'orders' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
            Pedidos Faturados ({{ $orders->total() }})
        </a>
    </div>

    @if($tab === 'quotes')
        <!-- Quotes List -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="p-4 pl-6">Número / Data</th>
                        <th class="p-4">Cliente / Empresa</th>
                        <th class="p-4">Itens</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Validade</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 pr-6 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($quotes as $quote)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-mono font-bold text-white">
                                {{ $quote->quote_number }}
                                <div class="text-[11px] text-slate-500 font-sans font-normal">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-white">{{ $quote->company?->trade_name ?? 'Cliente Avulso' }}</div>
                                <div class="text-xs text-slate-500">{{ $quote->contact?->name }}</div>
                            </td>
                            <td class="p-4 text-xs">
                                <span class="font-semibold text-slate-300">{{ $quote->items->count() }} itens</span>
                            </td>
                            <td class="p-4 font-mono font-bold text-emerald-400">
                                R$ {{ number_format($quote->total_amount, 2, ',', '.') }}
                            </td>
                            <td class="p-4 text-xs font-mono {{ $quote->isExpired() ? 'text-red-400' : 'text-slate-400' }}">
                                {{ $quote->expires_at?->format('d/m/Y') ?? 'N/A' }}
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold {{ $quote->status === 'converted_to_order' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    {{ strtoupper($quote->status) }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                @if($quote->status !== 'converted_to_order')
                                    <form method="POST" action="{{ route('portal.sales.convert', $quote) }}">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all shadow-md flex items-center gap-1.5 ml-auto">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Fechar Pedido</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-emerald-400 font-semibold">✓ Pedido Fechado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-sm text-slate-500">
                                Nenhuma cotação registrada no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pt-4">{{ $quotes->links() }}</div>
    @else
        <!-- Orders List -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="p-4 pl-6">Número / Data</th>
                        <th class="p-4">Cliente / Empresa</th>
                        <th class="p-4">Condições Pagamento</th>
                        <th class="p-4">Total</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 pr-6 text-right">Aprovação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4 pl-6 font-mono font-bold text-white">
                                {{ $order->order_number }}
                                <div class="text-[11px] text-slate-500 font-sans font-normal">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-white">{{ $order->company?->trade_name ?? 'Cliente Avulso' }}</div>
                                <div class="text-xs text-slate-500">{{ $order->contact?->name }}</div>
                            </td>
                            <td class="p-4 text-xs text-slate-400">
                                {{ $order->payment_terms ?? 'Padrão' }}
                            </td>
                            <td class="p-4 font-mono font-bold text-white">
                                R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold {{ $order->status === 'confirmed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                    {{ strtoupper($order->status) }}
                                </span>
                            </td>
                            <td class="p-4 pr-6 text-right">
                                @if($order->status === 'pending_approval')
                                    <form method="POST" action="{{ route('portal.sales.approve', $order) }}">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-all shadow-md ml-auto">
                                            Aprovar Alçada
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">Aprovado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-sm text-slate-500">
                                Nenhum pedido faturado registrado no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
