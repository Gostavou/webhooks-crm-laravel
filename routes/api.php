<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\CrmDealWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/webhook', [WebhookController::class, 'handle']);

// Webhook de Deal
Route::post(
    '/api/v1/webhooks/crm',
    [CrmDealWebhookController::class, 'handle']
);
