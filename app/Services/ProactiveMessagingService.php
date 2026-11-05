<?php

namespace App\Services;

use App\Models\ProactiveMessage;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Manages proactive messages – behavior-triggered outbound messages from the widget.
 */
class ProactiveMessagingService
{
    /**
     * Get eligible proactive messages for a widget + page URL combo.
     *
     * @return ProactiveMessage[]
     */
    public function getEligibleMessages(Widget $widget, string $pageUrl = ''): array
    {
        if (!$widget->proactive_enabled) {
            return [];
        }

        return ProactiveMessage::where('widget_id', $widget->id)
            ->where('is_active', true)
            ->get()
            ->filter(function (ProactiveMessage $pm) use ($pageUrl) {
                if ($pm->target_url_pattern && $pageUrl) {
                    return (bool) preg_match('/' . $pm->target_url_pattern . '/i', $pageUrl);
                }
                return true;
            })
            ->values()
            ->map(fn ($pm) => [
                'id'           => $pm->id,
                'message'      => $pm->message,
                'trigger_type' => $pm->trigger_type,
                'trigger_value'=> $pm->trigger_value,
                'quick_replies'=> $pm->quick_replies ?? [],
            ])
            ->toArray();
    }

    /**
     * Record that a proactive message was shown.
     */
    public function recordShown(int $proactiveMessageId): void
    {
        try {
            ProactiveMessage::where('id', $proactiveMessageId)->increment('shown_count');
        } catch (\Exception $e) {
            Log::debug('Failed to record proactive message shown: ' . $e->getMessage());
        }
    }

    /**
     * Record that a visitor clicked/responded to a proactive message.
     */
    public function recordClicked(int $proactiveMessageId): void
    {
        try {
            ProactiveMessage::where('id', $proactiveMessageId)->increment('clicked_count');
        } catch (\Exception $e) {
            Log::debug('Failed to record proactive message click: ' . $e->getMessage());
        }
    }

    /**
     * Build the simple widget-config proactive settings block.
     */
    public function buildWidgetConfig(Widget $widget, string $pageUrl = ''): array
    {
        $messages = $this->getEligibleMessages($widget, $pageUrl);

        return [
            'enabled'          => $widget->proactive_enabled,
            'trigger_seconds'  => $widget->proactive_trigger_seconds ?? 30,
            'trigger_pages'    => $widget->proactive_trigger_pages ?? 2,
            'message'          => $widget->proactive_message,
            'messages'         => $messages,
        ];
    }
}
