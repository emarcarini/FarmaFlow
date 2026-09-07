<?php

namespace App\Services\CRM;

use App\Models\Company;
use App\Models\CustomerScore;
use App\Models\Order;

class CustomerScoreService
{
    /**
     * Calcular score comercial explicável para uma empresa com base em Recência, Frequência e Valor.
     */
    public function calculateScore(Company $company): CustomerScore
    {
        $orders = Order::where('company_id', $company->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->get();

        $totalSpent = (float) $orders->sum('total_amount');
        $orderCount = $orders->count();
        $lastOrder = $orders->sortByDesc('created_at')->first();

        // 1. Recência (0 - 100)
        $recencyScore = 10;
        $recencyDays = null;
        if ($lastOrder) {
            $recencyDays = (int) now()->diffInDays($lastOrder->created_at);
            if ($recencyDays <= 15) {
                $recencyScore = 100;
            } elseif ($recencyDays <= 30) {
                $recencyScore = 80;
            } elseif ($recencyDays <= 60) {
                $recencyScore = 50;
            } elseif ($recencyDays <= 90) {
                $recencyScore = 30;
            } else {
                $recencyScore = 10;
            }
        }

        // 2. Frequência (0 - 100)
        $frequencyScore = min(100, $orderCount * 20);

        // 3. Valor / Monetário (0 - 100)
        $monetaryScore = 10;
        if ($totalSpent >= 50000) {
            $monetaryScore = 100;
        } elseif ($totalSpent >= 20000) {
            $monetaryScore = 80;
        } elseif ($totalSpent >= 5000) {
            $monetaryScore = 50;
        } elseif ($totalSpent > 0) {
            $monetaryScore = 30;
        }

        // Score Geral Ponderado: Recência 40%, Frequência 30%, Monetário 30%
        $overallScore = (int) round(($recencyScore * 0.40) + ($frequencyScore * 0.30) + ($monetaryScore * 0.30));

        // Tendência
        $trend = 'stable';
        if ($recencyDays !== null && $recencyDays > 45) {
            $trend = 'at_risk';
        } elseif ($orderCount >= 3 && $recencyDays !== null && $recencyDays <= 20) {
            $trend = 'growing';
        }

        $explanation = [
            'recency_days' => $recencyDays,
            'recency_text' => $recencyDays !== null ? "Última compra há {$recencyDays} dias" : "Sem histórico de compras registradas",
            'order_count' => $orderCount,
            'frequency_text' => "{$orderCount} pedidos realizados",
            'total_spent' => $totalSpent,
            'monetary_text' => "Faturamento acumulado de R$ " . number_format($totalSpent, 2, ',', '.'),
            'summary' => "Score calculado com base no modelo RFM (Recência 40%, Frequência 30%, Valor 30%).",
        ];

        return CustomerScore::updateOrCreate(
            ['company_id' => $company->id],
            [
                'recency_score' => $recencyScore,
                'frequency_score' => $frequencyScore,
                'monetary_score' => $monetaryScore,
                'overall_score' => $overallScore,
                'trend' => $trend,
                'explanation' => $explanation,
                'calculated_at' => now(),
            ]
        );
    }
}
