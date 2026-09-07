<?php

use App\Http\Controllers\Api\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

// Rotas de Webhook da Evolution API
Route::match(['GET', 'POST'], '/webhooks/evolution/{event?}', [EvolutionWebhookController::class, 'handle'])->name('api.webhooks.evolution');
Route::match(['GET', 'POST'], '/v1/webhooks/evolution/{event?}', [EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/webhook/evolution/{event?}', [EvolutionWebhookController::class, 'handle']);
Route::match(['GET', 'POST'], '/webhook/{event?}', [EvolutionWebhookController::class, 'handle']);
