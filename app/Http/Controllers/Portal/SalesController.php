<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Quote;
use App\Services\Sales\CommercialValidationException;
use App\Services\Sales\OrderService;
use App\Services\Sales\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService,
        protected OrderService $orderService
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'quotes');

        $quotes = Quote::with(['company', 'contact', 'items.product'])
            ->latest()
            ->paginate(15, ['*'], 'quotes_page');

        $orders = Order::with(['company', 'contact', 'items.product', 'approvals'])
            ->latest()
            ->paginate(15, ['*'], 'orders_page');

        return view('portal.sales.index', compact('quotes', 'orders', 'tab'));
    }

    public function convertToOrder(Request $request, Quote $quote): RedirectResponse
    {
        try {
            $order = $this->orderService->createFromQuote($quote, [
                'created_via' => 'portal_manual',
                'actor_type' => 'user',
            ]);

            $msg = "Cotação convertida no Pedido {$order->order_number} com sucesso!";
            if ($order->requires_approval) {
                $msg .= " (Atenção: Pedido gerado como pendente de aprovação por alçada de desconto).";
            }

            return redirect()->route('portal.sales', ['tab' => 'orders'])->with('success', $msg);
        } catch (CommercialValidationException $e) {
            return redirect()->route('portal.sales', ['tab' => 'quotes'])->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->route('portal.sales', ['tab' => 'quotes'])->with('error', 'Erro ao converter pedido: ' . $e->getMessage());
        }
    }

    public function approveOrder(Request $request, Order $order): RedirectResponse
    {
        $this->orderService->approveOrder($order, auth()->user(), 'Aprovado pelo gestor/administrador no portal.');
        return redirect()->route('portal.sales', ['tab' => 'orders'])->with('success', "Pedido {$order->order_number} aprovado com sucesso!");
    }
}
