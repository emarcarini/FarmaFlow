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

// Rotas do Portal (Acesso restrito para usuários autenticados)
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 1. Dashboard Executivo
    Route::get('/', [DashboardController::class, 'index'])->name('portal.dashboard');

    // 2. Inbox WhatsApp
    Route::get('/inbox', [InboxController::class, 'index'])->name('portal.inbox');
    Route::post('/inbox/{conversation}/send', [InboxController::class, 'sendMessage'])->name('portal.inbox.send');
    Route::post('/inbox/{conversation}/handover', [InboxController::class, 'toggleHandover'])->name('portal.inbox.handover');

    // 3. CRM e Clientes
    Route::get('/crm', [CrmController::class, 'index'])->name('portal.crm');
    Route::get('/crm/{company}', [CrmController::class, 'show'])->name('portal.crm.show');

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
});
