<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Problems shown in Admin > Widget monitor (from the widget in browsers and from the server). */
class WidgetEvents
{
    public const TYPES = [
        'provider_down' => 'Provider down', 'reply_failed' => 'Stream failed', 'failover' => 'Failover',
        'config_failed' => 'Widget failed to load', 'voice_failed' => 'Voice failed', 'client_freeze' => 'Client freeze',
    ];

    public static function record(?int $widgetId, string $type, string $message = '', string $severity = 'error'): void
    {
        if (! $widgetId) return;
        try {
            DB::table('widget_events')->insert(['widget_id' => $widgetId, 'type' => $type, 'severity' => $severity, 'message' => mb_substr($message, 0, 500), 'page_url' => mb_substr((string) request()?->header('Referer'), 0, 500), 'created_at' => now()]);
        } catch (\Throwable) {
            // monitoring must never break a chat
        }
    }
}
