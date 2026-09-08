<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Portal\CatalogController;
use App\Http\Controllers\Portal\CrmController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentationController;
use App\Http\Controllers\Portal\InboxController;
use App\Http\Controllers\Portal\SalesController;
use App\Http\Controllers\Portal\TaskController;
use Illuminate\Support\Facades\Route;

// Autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Webhook Endpoints da Evolution API (Sem autenticação de sessão, com suporte a qualquer variação de rota e sub-evento)
Route::match(['GET', 'POST'], '/webhooks/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/v1/webhooks/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/webhook/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/webhook/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/api/webhooks/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/api/v1/webhooks/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/api/webhook/evolution/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/api/webhook/{event?}', [\App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle']);

// Rotas do Portal (Acesso restrito para usuários autenticados)
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 1. Dashboard Executivo
    Route::get('/', [DashboardController::class, 'index'])->name('portal.dashboard');

    // 2. Inbox WhatsApp
    Route::get('/inbox', [InboxController::class, 'index'])->name('portal.inbox');
    Route::get('/inbox/contacts', [InboxController::class, 'contacts'])->name('portal.inbox.contacts');
    Route::post('/inbox/start', [InboxController::class, 'startConversation'])->name('portal.inbox.start');
    Route::post('/inbox/{conversation}/send', [InboxController::class, 'sendMessage'])->name('portal.inbox.send');
    Route::post('/inbox/{conversation}/handover', [InboxController::class, 'toggleHandover'])->name('portal.inbox.handover');
    Route::post('/inbox/{conversation}/archive', [InboxController::class, 'archive'])->name('portal.inbox.archive');
    Route::post('/inbox/{conversation}/unarchive', [InboxController::class, 'unarchive'])->name('portal.inbox.unarchive');

    // Regras e Instruções Dinâmicas do Bot IA
    Route::get('/bot-rules', [\App\Http\Controllers\Portal\BotRuleController::class, 'index'])->name('portal.bot-rules.index');
    Route::post('/bot-rules', [\App\Http\Controllers\Portal\BotRuleController::class, 'store'])->name('portal.bot-rules.store');
    Route::post('/bot-rules/{botRule}/toggle', [\App\Http\Controllers\Portal\BotRuleController::class, 'toggle'])->name('portal.bot-rules.toggle');
    Route::delete('/bot-rules/{botRule}', [\App\Http\Controllers\Portal\BotRuleController::class, 'destroy'])->name('portal.bot-rules.destroy');

    // 3. CRM e Clientes
    Route::get('/crm', [CrmController::class, 'index'])->name('portal.crm');
    Route::get('/crm/{company}', [CrmController::class, 'show'])->name('portal.crm.show');
    Route::post('/crm/{company}/contacts', [CrmController::class, 'storeContact'])->name('portal.crm.contacts.store');
    Route::delete('/crm/{company}/contacts/{contact}', [CrmController::class, 'destroyContact'])->name('portal.crm.contacts.destroy');

    // 4. Catálogo & Preços
    Route::get('/catalogo', [CatalogController::class, 'index'])->name('portal.catalog');

    // 5. Cotações & Pedidos
    Route::get('/vendas', [SalesController::class, 'index'])->name('portal.sales');
    Route::post('/vendas/quotes/{quote}/convert', [SalesController::class, 'convertToOrder'])->name('portal.sales.convert');
    Route::post('/vendas/orders/{order}/approve', [SalesController::class, 'approveOrder'])->name('portal.sales.approve');

    // 6. Tarefas & Follow-ups
    Route::get('/tarefas', [TaskController::class, 'index'])->name('portal.tasks');
    Route::post('/tarefas/{task}/complete', [TaskController::class, 'complete'])->name('portal.tasks.complete');

    // 7. Central de Documentação do Representante (Exclusivo para usuários autenticados)
    Route::get('/docs', [DocumentationController::class, 'index'])->name('portal.docs');

    // 8. Gestão de Conexão WhatsApp / Evolution API
    Route::get('/whatsapp/status', [\App\Http\Controllers\Portal\WhatsAppConnectionController::class, 'status'])->name('portal.whatsapp.status');
    Route::get('/whatsapp/qrcode', [\App\Http\Controllers\Portal\WhatsAppConnectionController::class, 'getQrCode'])->name('portal.whatsapp.qrcode');
    Route::post('/whatsapp/disconnect', [\App\Http\Controllers\Portal\WhatsAppConnectionController::class, 'disconnect'])->name('portal.whatsapp.disconnect');
    Route::post('/whatsapp/sync-webhooks', [\App\Http\Controllers\Portal\WhatsAppConnectionController::class, 'syncWebhooks'])->name('portal.whatsapp.sync');
    Route::post('/whatsapp/delete-instance', [\App\Http\Controllers\Portal\WhatsAppConnectionController::class, 'deleteOrphanInstance'])->name('portal.whatsapp.delete-instance');

    // 9. Configurações & Regras do Robô IA
    Route::get('/configuracoes/bot', [\App\Http\Controllers\Portal\BotSettingsController::class, 'indexSettings'])->name('portal.bot.settings');
    Route::post('/configuracoes/bot', [\App\Http\Controllers\Portal\BotSettingsController::class, 'updateSettings'])->name('portal.bot.settings.update');
    Route::get('/regras', [\App\Http\Controllers\Portal\BotSettingsController::class, 'indexRules'])->name('portal.bot.rules');
    Route::post('/regras', [\App\Http\Controllers\Portal\BotSettingsController::class, 'storeRule'])->name('portal.bot.rules.store');
    Route::patch('/regras/{rule}/toggle', [\App\Http\Controllers\Portal\BotSettingsController::class, 'toggleRule'])->name('portal.bot.rules.toggle');
    Route::put('/regras/{rule}', [\App\Http\Controllers\Portal\BotSettingsController::class, 'updateRule'])->name('portal.bot.rules.update');
    Route::delete('/regras/{rule}', [\App\Http\Controllers\Portal\BotSettingsController::class, 'destroyRule'])->name('portal.bot.rules.destroy');

    // 10. Módulo Administrativo (Apenas Admin)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/representantes', [\App\Http\Controllers\Portal\RepresentativeAdminController::class, 'index'])->name('representatives.index');
        Route::post('/representantes', [\App\Http\Controllers\Portal\RepresentativeAdminController::class, 'store'])->name('representatives.store');
        Route::put('/representantes/{representative}', [\App\Http\Controllers\Portal\RepresentativeAdminController::class, 'update'])->name('representatives.update');
        Route::post('/representantes/{representative}/toggle', [\App\Http\Controllers\Portal\RepresentativeAdminController::class, 'toggleStatus'])->name('representatives.toggle');
        Route::delete('/representantes/{representative}', [\App\Http\Controllers\Portal\RepresentativeAdminController::class, 'destroy'])->name('representatives.destroy');
    });
});
