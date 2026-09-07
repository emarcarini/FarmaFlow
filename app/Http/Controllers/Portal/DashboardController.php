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
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthlyOrders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->get();

        $monthlyRevenue = (float) $monthlyOrders->sum('total_amount');
        $ordersCount = $monthlyOrders->count();
        $monthlyGoal = 150000.00; // Meta padrão R$ 150k
        $goalProgress = min(100, round(($monthlyRevenue / $monthlyGoal) * 100, 1));

        $activeQuotesCount = Quote::whereIn('status', ['draft', 'sent'])->count();
        $activeQuotesAmount = (float) Quote::whereIn('status', ['draft', 'sent'])->sum('total_amount');

        $activeCompaniesCount = Company::where('status', 'active')->count();
        $atRiskCompaniesCount = Company::where('status', 'at_risk')->count();

        $pendingTasksCount = Task::where('status', 'pending')->count();
        $overdueTasksCount = Task::where('status', 'pending')->where('due_date', '<', now())->count();

        $handoverConversationsCount = Conversation::where('status', 'human_takeover')->count();

        $recentOrders = Order::with('company', 'contact')->latest()->take(6)->get();
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
            'activeCampaigns'
        ));
    }
}
