<?php

use App\Http\Controllers\Api\WidgetApiController;
use App\Http\Controllers\Api\ChannelApiController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\WidgetRateLimit;
use Illuminate\Support\Facades\Route;

// Stripe webhook (no auth, no CSRF — API routes are exempt by default)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

// Widget API (public, CORS-enabled, rate-limited)
Route::prefix('widget')->middleware([WidgetRateLimit::class])->group(function () {

    // Configuration
    Route::get('/{widgetUuid}/config', [WidgetApiController::class, 'config']);

    // Conversations
    Route::post('/{widgetUuid}/conversations', [WidgetApiController::class, 'startConversation']);

    // Messaging
    Route::post('/{widgetUuid}/conversations/{conversationUuid}/messages', [WidgetApiController::class, 'sendMessage']);
    Route::post('/{widgetUuid}/conversations/{conversationUuid}/stream', [WidgetApiController::class, 'streamMessage']);

    // Polling for agent messages (real-time delivery without WebSockets)
    Route::get('/{widgetUuid}/conversations/{conversationUuid}/poll', [WidgetApiController::class, 'pollMessages']);

    // Handoff
    Route::post('/{widgetUuid}/conversations/{conversationUuid}/handoff', [WidgetApiController::class, 'requestHumanHandoff']);

    // CSAT Feedback
    Route::post('/{widgetUuid}/conversations/{conversationUuid}/feedback', [WidgetApiController::class, 'submitFeedback']);

    // Widget-side errors (Admin > Widget monitor)
    Route::post('/{widgetUuid}/event', function (\Illuminate\Http\Request $request, string $widgetUuid) {
        $w = \App\Models\Widget::where('uuid', $widgetUuid)->first();
        $request->merge((array) json_decode($request->getContent(), true)); // sent as text/plain by sendBeacon
        if ($w) \Illuminate\Support\Facades\DB::table('widget_events')->insert([
            'widget_id' => $w->id, 'type' => mb_substr((string) $request->input('type', 'error'), 0, 40),
            'message' => mb_substr((string) $request->input('message', ''), 0, 500), 'page_url' => mb_substr((string) $request->input('page_url', ''), 0, 500), 'created_at' => now(),
        ]);
        return response()->noContent();
    });

    // Lead form
    Route::post('/{widgetUuid}/lead', [\App\Http\Controllers\Api\LeadCaptureController::class, 'store']);

    // Offline messages (when visitor leaves email)
    Route::post('/{widgetUuid}/offline', [WidgetApiController::class, 'submitOfflineMessage']);

    // Message reactions (emoji)
    Route::post('/{widgetUuid}/conversations/{conversationUuid}/messages/{messageUuid}/react', [WidgetApiController::class, 'reactToMessage']);

    // Proactive message tracking
    Route::post('/{widgetUuid}/proactive/track', [WidgetApiController::class, 'trackProactive']);

    // Bookings
    Route::get('/{widgetUuid}/booking', [\App\Http\Controllers\Api\BookingApiController::class, 'show']);
    Route::get('/{widgetUuid}/booking/slots', [\App\Http\Controllers\Api\BookingApiController::class, 'slots']);
    Route::post('/{widgetUuid}/booking', [\App\Http\Controllers\Api\BookingApiController::class, 'store'])->middleware('throttle:6,1,bk-store');
    Route::post('/{widgetUuid}/booking/lookup', [\App\Http\Controllers\Api\BookingApiController::class, 'lookup'])->middleware('throttle:10,1,bk-lookup');
    Route::post('/{widgetUuid}/booking/manage/{token}/cancel', [\App\Http\Controllers\Api\BookingApiController::class, 'manageCancel'])->middleware('throttle:10,1,bk-manage');
    Route::post('/{widgetUuid}/booking/manage/{token}/reschedule', [\App\Http\Controllers\Api\BookingApiController::class, 'manageReschedule'])->middleware('throttle:10,1,bk-manage');
});

// PWA Routes (authenticated)
Route::middleware(['auth'])->prefix('pwa')->group(function () {
    Route::get('/status', [\App\Http\Controllers\PwaController::class, 'status']);
    Route::post('/install', [\App\Http\Controllers\PwaController::class, 'trackInstall']);
    
    // Push notifications
    Route::post('/push/subscribe', [\App\Http\Controllers\PwaController::class, 'subscribePush']);
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PwaController::class, 'unsubscribePush']);
    Route::post('/push/test', [\App\Http\Controllers\PwaController::class, 'sendTestNotification']);
});

// Channel Management (authenticated)
Route::middleware(['auth'])->prefix('channels')->group(function () {
    // Channel configuration
    Route::get('/types', [ChannelApiController::class, 'getChannelTypes']);
    Route::get('/{widgetId}', [ChannelApiController::class, 'listChannels']);
    Route::post('/{widgetId}/configure', [ChannelApiController::class, 'configureChannel']);
    Route::put('/{channelId}', [ChannelApiController::class, 'updateChannel']);
    Route::delete('/{channelId}', [ChannelApiController::class, 'deleteChannel']);
    Route::post('/{channelId}/test', [ChannelApiController::class, 'testChannel']);
    Route::get('/{channelId}/status', [ChannelApiController::class, 'getChannelStatus']);
    
    // Channel messages
    Route::get('/{channelId}/messages', [ChannelApiController::class, 'getChannelMessages']);
    Route::post('/{channelId}/messages', [ChannelApiController::class, 'sendChannelMessage']);
    
    // Channel contacts
    Route::get('/{channelId}/contacts', [ChannelApiController::class, 'getChannelContacts']);
});

// Channel Webhooks (public, no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp', [ChannelApiController::class, 'handleWhatsAppWebhook']);
    Route::post('/facebook', [ChannelApiController::class, 'handleFacebookWebhook']);
    Route::post('/instagram', [ChannelApiController::class, 'handleInstagramWebhook']);
    Route::post('/telegram', [ChannelApiController::class, 'handleTelegramWebhook']);
    Route::post('/discord', [ChannelApiController::class, 'handleDiscordWebhook']);
    Route::post('/slack', [ChannelApiController::class, 'handleSlackWebhook']);
    Route::post('/twitter', [ChannelApiController::class, 'handleTwitterWebhook']);
    Route::post('/email', [ChannelApiController::class, 'handleEmailWebhook']);
    Route::post('/sms', [ChannelApiController::class, 'handleSmsWebhook']);
});

// Health check (no auth required)
// REST API for CRMs / Zapier / Make (API key auth)
Route::prefix('v1')->middleware(\App\Http\Middleware\AuthenticateApiKey::class)->group(function () {
    Route::get('/widgets/{widget}/booking', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'services']);
    Route::get('/widgets/{widget}/availability', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'availability']);
    Route::get('/appointments', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'index']);
    Route::post('/appointments', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'store']);
    Route::get('/appointments/{id}', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'show'])->whereNumber('id');
    Route::patch('/appointments/{id}', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'update'])->whereNumber('id');
    Route::delete('/appointments/{id}', [\App\Http\Controllers\Api\V1\AppointmentApiController::class, 'destroy'])->whereNumber('id');
});

Route::get('/health', [\App\Http\Controllers\PwaController::class, 'health']);

// CMS content sync (WordPress plugin). Auth: X-Widget-Token header.
Route::prefix('sync/{uuid}')->middleware('throttle:120,1')->group(function () {
    Route::get('/ping', [\App\Http\Controllers\Api\ContentSyncController::class, 'ping']);
    Route::post('/pages', [\App\Http\Controllers\Api\ContentSyncController::class, 'upsert']);
    Route::delete('/pages', [\App\Http\Controllers\Api\ContentSyncController::class, 'remove']);
});
