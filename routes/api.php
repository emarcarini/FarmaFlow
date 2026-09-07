<?php

use App\Http\Controllers\Api\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

// Rotas de Webhook da Evolution API
Route::post('/webhooks/evolution', [EvolutionWebhookController::class, 'handle'])->name('api.webhooks.evolution');
Route::post('/v1/webhooks/evolution', [EvolutionWebhookController::class, 'handle']);
Route::post('/webhook/evolution', [EvolutionWebhookController::class, 'handle']);
Route::post('/webhook', [EvolutionWebhookController::class, 'handle']);
