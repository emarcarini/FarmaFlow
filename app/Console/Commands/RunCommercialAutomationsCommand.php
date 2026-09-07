<?php

namespace App\Console\Commands;

use App\Services\Automation\CommercialAutomationService;
use Illuminate\Console\Command;

class RunCommercialAutomationsCommand extends Command
{
    protected $signature = 'commercial:automate';
    protected $description = 'Executa rotinas automáticas de follow-up, detecção de inatividade e alertas de reposição';

    public function handle(CommercialAutomationService $service): int
    {
        $this->info("Iniciando rotinas de automação comercial...");

        $unanswered = $service->checkUnansweredQuotes(2);
        $this->line("• Cotações paradas processadas: {$unanswered}");

        $inactive = $service->checkInactiveCustomers(30);
        $this->line("• Clientes em risco de inatividade alertados: {$inactive}");

        $repurchases = $service->checkRepurchaseCycles();
        $this->line("• Oportunidades de reposição geradas: {$repurchases}");

        $this->info("Rotinas de automação concluídas com sucesso!");
        return Command::SUCCESS;
    }
}
