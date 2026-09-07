<?php

use App\Http\Controllers\Api\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/evolution', [EvolutionWebhookController::class, 'handle'])->name('api.webhooks.evolution');
