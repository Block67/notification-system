<?php

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public route to generate API keys (you should protect this in production)
Route::prefix('v1/api-keys')->group(function () {
    Route::post('generate', [ApiKeyController::class, 'generate']);
    Route::get('/', [ApiKeyController::class, 'list']);
    Route::get('{id}', [ApiKeyController::class, 'show']);
    Route::put('{id}', [ApiKeyController::class, 'update']);
    Route::delete('{id}', [ApiKeyController::class, 'delete']);
    Route::post('{id}/regenerate-secret', [ApiKeyController::class, 'regenerateSecret']);
});

// Protected notification routes
Route::prefix('v1/notifications')->middleware('api.key')->group(function () {
    
    // Web Push routes
    Route::prefix('web-push')->middleware('api.key:web_push')->group(function () {
        Route::post('send', [NotificationController::class, 'sendWebPush']);
        Route::post('subscribe', [NotificationController::class, 'subscribe']);
        Route::post('unsubscribe', [NotificationController::class, 'unsubscribe']);
    });

    // Email routes
    Route::prefix('email')->middleware('api.key:email')->group(function () {
        Route::post('send', [NotificationController::class, 'sendEmail']);
    });

    // WhatsApp routes
    Route::prefix('whatsapp')->middleware('api.key:whatsapp')->group(function () {
        Route::post('send', [NotificationController::class, 'sendWhatsApp']);
    });

    // Telegram routes
    Route::prefix('telegram')->middleware('api.key:telegram')->group(function () {
        Route::post('send', [NotificationController::class, 'sendTelegram']);
    });

    // Discord routes
    Route::prefix('discord')->middleware('api.key:discord')->group(function () {
        Route::post('send', [NotificationController::class, 'sendDiscord']);
    });

    // Bulk send (requires appropriate permission)
    Route::post('bulk-send', [NotificationController::class, 'bulkSend']);

    // Logs and stats
    Route::get('logs', [NotificationController::class, 'getLogs']);
    Route::get('stats', [NotificationController::class, 'getStats']);
});
