<?php

use App\Models\WhatsappConnection;
use App\Services\WhatsappWebhookIngestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/evolution/webhook/{secret}', function (Request $request, string $secret, WhatsappWebhookIngestor $ingestor) {
    $connection = WhatsappConnection::query()
        ->where('webhook_secret', $secret)
        ->firstOrFail();

    $ingestor->ingest($connection->load('tenant'), $request->all());

    return response()->json(['received' => true]);
})->name('evolution.webhook');
