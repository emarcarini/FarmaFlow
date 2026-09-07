<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $isRep = $user->isRepresentative();
        $repId = $user->representative?->id;

        // 1. Pedidos do Mês
        $ordersQuery = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);

        if ($isRep && $repId) {
            $ordersQuery->where('representative_id', $repId);
        }

        $monthlyOrders = $ordersQuery->get();
        $monthlyRevenue = (float) $monthlyOrders->sum('total_amount');
        $ordersCount = $monthlyOrders->count();
        $monthlyGoal = $isRep ? 60000.00 : 250000.00; // Meta individual R$ 60k, Global R$ 250k
        $goalProgress = min(100, round(($monthlyRevenue / $monthlyGoal) * 100, 1));

        // 2. Cotações Ativas
        $quotesQuery = Quote::whereIn('status', ['draft', 'sent']);
        if ($isRep && $repId) {
            $quotesQuery->where('representative_id', $repId);
        }
        $activeQuotesCount = $quotesQuery->count();
        $activeQuotesAmount = (float) $quotesQuery->sum('total_amount');

        // 3. Empresas Ativas / Em Risco
        $companiesQuery = Company::query();
        if ($isRep && $repId) {
            $companiesQuery->where('representative_id', $repId);
        }
        $activeCompaniesCount = (clone $companiesQuery)->where('status', 'active')->count();
        $atRiskCompaniesCount = (clone $companiesQuery)->where('status', 'at_risk')->count();

        // 4. Tarefas e Follow-ups
        $tasksQuery = Task::where('status', 'pending');
        if ($isRep && $repId) {
            $tasksQuery->where('representative_id', $repId);
        }
        $pendingTasksCount = (clone $tasksQuery)->count();
        $overdueTasksCount = (clone $tasksQuery)->where('due_date', '<', now())->count();

        // 5. Conversas em Handover
        $convQuery = Conversation::where('status', 'human_takeover');
        if ($isRep && $repId) {
            $convQuery->where('representative_id', $repId);
        }
        $handoverConversationsCount = $convQuery->count();

        // 6. Pedidos Recentes
        $recentOrdersQuery = Order::with('company', 'contact')->latest();
        if ($isRep && $repId) {
            $recentOrdersQuery->where('representative_id', $repId);
        }
        $recentOrders = $recentOrdersQuery->take(6)->get();

        $topProducts = Product::where('is_active', true)->take(5)->get();
        $activeCampaigns = CommercialCampaign::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('products.product')
            ->get();

        return view('portal.dashboard', compact(
            'monthlyRevenue',
            'monthlyGoal',
            'goalProgress',
            'ordersCount',
            'activeQuotesCount',
            'activeQuotesAmount',
            'activeCompaniesCount',
            'atRiskCompaniesCount',
            'pendingTasksCount',
            'overdueTasksCount',
            'handoverConversationsCount',
            'recentOrders',
            'topProducts',
            'activeCampaigns',
            'isRep'
        ));
    }
}
