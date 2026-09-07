<?php

namespace App\Services\Automation;

use App\Models\Company;
use App\Models\CustomerProductHistory;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Representative;
use App\Models\Task;
use App\Services\Audit\AuditLogService;

class CommercialAutomationService
{
    /**
     * 1. Follow-up automático para cotações sem resposta após X dias.
     */
    public function checkUnansweredQuotes(int $daysThreshold = 2): int
    {
        $cutoff = now()->subDays($daysThreshold);

        $pendingQuotes = Quote::whereIn('status', ['draft', 'sent'])
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('opportunity.tasks', fn($q) => $q->where('status', 'pending'))
            ->get();

        $count = 0;
        foreach ($pendingQuotes as $quote) {
            $task = Task::create([
                'company_id' => $quote->company_id,
                'contact_id' => $quote->contact_id,
                'representative_id' => $quote->representative_id,
                'title' => "Follow-up de Cotação Parada: {$quote->quote_number}",
                'description' => "Cotação no valor de R$ " . number_format($quote->total_amount, 2, ',', '.') . " está sem resposta há mais de {$daysThreshold} dias.",
                'task_type' => 'followup',
                'priority' => 'high',
                'due_date' => now()->addDay()->setHour(10)->setMinute(0),
                'status' => 'pending',
            ]);

            Followup::create([
                'task_id' => $task->id,
                'company_id' => $quote->company_id,
                'contact_id' => $quote->contact_id,
                'representative_id' => $quote->representative_id,
                'quote_id' => $quote->id,
                'scheduled_for' => now()->addDay(),
                'trigger_type' => 'unanswered_quote',
                'status' => 'scheduled',
                'notes' => "Gatilho automático: cotação sem resposta há {$daysThreshold} dias.",
            ]);

            $count++;
        }

        if ($count > 0) {
            AuditLogService::log(
                action: 'automation.unanswered_quotes',
                oldValues: null,
                newValues: ['quotes_processed' => $count],
                actorType: 'system',
                actorName: 'CommercialAutomationService'
            );
        }

        return $count;
    }

    /**
     * 2. Detectar clientes inativos (sem compras há mais de X dias).
     */
    public function checkInactiveCustomers(int $daysThreshold = 30): int
    {
        $cutoff = now()->subDays($daysThreshold);

        $inactiveCompanies = Company::where('status', 'active')
            ->whereDoesntHave('orders', fn($q) => $q->where('created_at', '>=', $cutoff))
            ->whereDoesntHave('tasks', fn($q) => $q->where('status', 'pending'))
            ->get();

        $count = 0;
        foreach ($inactiveCompanies as $company) {
            $company->update(['status' => 'at_risk']);

            Task::create([
                'company_id' => $company->id,
                'representative_id' => $company->representative_id,
                'title' => "Recuperação de Cliente: {$company->trade_name}",
                'description' => "Cliente sem novos pedidos há mais de {$daysThreshold} dias. Sugerida abordagem com campanha ou condição especial.",
                'task_type' => 'call',
                'priority' => 'medium',
                'due_date' => now()->addDays(2),
                'status' => 'pending',
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * 3. Alertas de Reposição Baseados em Periodicidade Histórica.
     */
    public function checkRepurchaseCycles(): int
    {
        $dueHistories = CustomerProductHistory::where('next_estimated_purchase_at', '<=', now()->toDateString())
            ->whereDoesntHave('company.tasks', fn($q) => $q->where('status', 'pending')->where('task_type', 'whatsapp'))
            ->with('company', 'contact', 'product')
            ->get();

        $count = 0;
        foreach ($dueHistories as $history) {
            Task::create([
                'company_id' => $history->company_id,
                'contact_id' => $history->contact_id,
                'representative_id' => $history->company?->representative_id,
                'title' => "Oportunidade de Reposição: {$history->product?->name}",
                'description' => "Janela estimada de reposição para {$history->company?->trade_name}. Última compra de {$history->total_quantity_purchased} unidades em {$history->last_purchased_at?->format('d/m/Y')}.",
                'task_type' => 'whatsapp',
                'priority' => 'medium',
                'due_date' => now()->addDay(),
                'status' => 'pending',
            ]);

            $count++;
        }

        return $count;
    }
}
