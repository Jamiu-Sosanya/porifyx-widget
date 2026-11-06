<?php

namespace App\Services;

use App\Mail\HandoffRequestNotification;
use App\Mail\NewLeadNotification;
use App\Mail\OfflineMessageNotification;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\OfflineMessage;
use App\Models\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends notifications to Slack, Discord, and Email on key events.
 */
class NotificationChannelService
{
    // ── Lead Qualified ────────────────────────────────────────────────────────

    public function notifyLeadQualified(Widget $widget, Lead $lead): void
    {
        if (!$widget->notify_on_lead) return;

        $title   = "🔥 New Lead Qualified – {$widget->name}";
        $temp    = ucfirst($lead->temperature ?? 'warm');
        $score   = $lead->score ?? 0;
        $name    = $lead->name ?? 'Anonymous';
        $email   = $lead->email ?? '—';
        $company = $lead->company ?? '—';
        $service = $lead->service_interest ?? '—';

        $text = "*{$title}*\n" .
                "• Name: {$name}\n" .
                "• Email: {$email}\n" .
                "• Company: {$company}\n" .
                "• Interest: {$service}\n" .
                "• Temperature: {$temp} | Score: {$score}/100";

        $this->sendSlack($widget, $title, $text, $this->leadColor($lead->temperature));
        $this->sendDiscord($widget, $title, $text, $this->leadDiscordColor($lead->temperature));

        $to = $widget->notification_email ?? $widget->user?->email;
        if ($to) {
            try {
                Mail::to($to)->queue(new NewLeadNotification($lead, $widget));
            } catch (\Exception $e) {
                Log::warning('Email notification failed: ' . $e->getMessage());
            }
        }
    }

    // ── Human Handoff Requested ───────────────────────────────────────────────

    public function notifyHandoffRequested(Widget $widget, Conversation $conversation): void
    {
        if (!$widget->notify_on_handoff) return;

        $visitor = $conversation->visitor;
        $name    = $visitor?->name ?? 'Anonymous';
        $email   = $visitor?->email ?? '—';
        $page    = $conversation->current_page_url ?? '—';

        $title = "🙋 Human Handoff Requested – {$widget->name}";
        $text  = "*{$title}*\n" .
                 "• Visitor: {$name}\n" .
                 "• Email: {$email}\n" .
                 "• Page: {$page}\n" .
                 "• Messages: {$conversation->message_count}";

        $this->sendSlack($widget, $title, $text, '#f59e0b');
        $this->sendDiscord($widget, $title, $text, 0xf59e0b);

        $to = $widget->notification_email ?? $widget->user?->email;
        if ($to) {
            try {
                Mail::to($to)->queue(new HandoffRequestNotification($conversation, $widget));
            } catch (\Exception $e) {
                Log::warning('Email notification failed: ' . $e->getMessage());
            }
        }
    }

    // ── Offline Message Received ──────────────────────────────────────────────

    public function notifyOfflineMessage(Widget $widget, OfflineMessage $offlineMessage): void
    {
        $name    = $offlineMessage->name ?? 'Anonymous';
        $email   = $offlineMessage->email;
        $message = $offlineMessage->message;

        $title = "📩 Offline Message – {$widget->name}";
        $text  = "*{$title}*\n" .
                 "• From: {$name} <{$email}>\n" .
                 "• Message: " . substr($message, 0, 200);

        $this->sendSlack($widget, $title, $text, '#ff6a2b');
        $this->sendDiscord($widget, $title, $text, 0x6366f1);

        $to = $widget->notification_email ?? $widget->user?->email;
        if ($to) {
            try {
                Mail::to($to)->queue(new OfflineMessageNotification($offlineMessage, $widget));
            } catch (\Exception $e) {
                Log::warning('Email notification failed: ' . $e->getMessage());
            }
        }
    }

    // ── Slack ─────────────────────────────────────────────────────────────────

    protected function sendSlack(Widget $widget, string $title, string $text, string $color = '#ff6a2b'): void
    {
        $url = $widget->slack_webhook_url;
        if (!$url) return;

        try {
            Http::timeout(8)->post($url, [
                'attachments' => [[
                    'color'    => $color,
                    'text'     => $text,
                    'fallback' => $title,
                    'footer'   => 'PoriFyx Widget',
                    'ts'       => time(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Slack notification failed: ' . $e->getMessage());
        }
    }

    // ── Discord ───────────────────────────────────────────────────────────────

    protected function sendDiscord(Widget $widget, string $title, string $text, int $color = 0x6366f1): void
    {
        $url = $widget->discord_webhook_url;
        if (!$url) return;

        // Convert Slack-style markdown to plain text for Discord
        $desc = str_replace('*', '**', $text);

        try {
            Http::timeout(8)->post($url, [
                'embeds' => [[
                    'title'       => $title,
                    'description' => $desc,
                    'color'       => $color,
                    'footer'      => ['text' => 'PoriFyx Widget'],
                    'timestamp'   => now()->toISOString(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord notification failed: ' . $e->getMessage());
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function leadColor(string $temp): string
    {
        return match ($temp) {
            'hot'  => '#ef4444',
            'warm' => '#f97316',
            default => '#3b82f6',
        };
    }

    protected function leadDiscordColor(string $temp): int
    {
        return match ($temp) {
            'hot'  => 0xef4444,
            'warm' => 0xf97316,
            default => 0x3b82f6,
        };
    }
}
