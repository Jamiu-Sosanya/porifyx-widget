<?php

namespace App\Services;

use App\Jobs\SendWidgetWebhook;
use App\Models\Widget;

class WidgetWebhookService
{
    public function dispatch(Widget $widget, string $event, array $data = []): void
    {
        if (! $widget->webhook_enabled || ! $widget->webhook_url) {
            return;
        }

        SendWidgetWebhook::dispatch($widget->id, $event, $data);
    }
}
